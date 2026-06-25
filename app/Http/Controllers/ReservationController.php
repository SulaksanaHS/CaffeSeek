<?php

namespace App\Http\Controllers;

use App\Mail\ReservationInvoiceMail;
use App\Models\Cafe;
use App\Models\Reservation;
use App\Models\ReservationItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ReservationController extends Controller
{
    /**
     * Menyimpan data booking baru dari halaman Detail Kafe (Customer)
     */
    public function store(Request $request)
    {
        $request->validate([
            'cafe_id' => 'required|exists:cafes,id',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_whatsapp' => 'required|string|max:20',
            'reservation_date' => 'required|date',
            'cart_items' => 'required|array|min:1',
            'cart_items.*.type' => 'required|string',
            'cart_items.*.name' => 'required|string',
            'cart_items.*.price' => 'required|numeric|min:0',
            'cart_items.*.quantity' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();
        try {
            $totalPrice = 0;
            foreach ($request->cart_items as $item) {
                if ($item['type'] === 'menu') {
                    $totalPrice += ($item['price'] * $item['quantity']);
                }
            }

            $reservation = Reservation::create([
                'cafe_id' => $request->cafe_id,
                'customer_name' => $request->customer_name,
                'customer_email' => $request->customer_email,
                'customer_whatsapp' => $request->customer_whatsapp,
                'reservation_date' => $request->reservation_date,
                'status' => 'pending',
                'total_price' => $totalPrice,
            ]);

            foreach ($request->cart_items as $item) {
                ReservationItem::create([
                    'reservation_id' => $reservation->id,
                    'item_type' => $item['type'],
                    'name' => $item['name'],
                    'price' => $item['price'],
                    'quantity' => $item['quantity'],
                ]);
            }

            DB::commit();

            return redirect()->route('reservations.payment', $reservation->id);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Gagal membuat reservasi: ' . $e->getMessage()]);
        }
    }

    /**
     * Menampilkan halaman Pembayaran / Upload Bukti Transfer (Customer)
     */
    public function payment($id)
    {
        $reservation = Reservation::with(['cafe', 'items'])->findOrFail($id);

        return Inertia::render('customer/reservations/payment', [
            'reservation' => $reservation,
        ]);
    }

    /**
     * Memproses upload foto bukti transfer (Customer)
     */
    public function uploadProof(Request $request, $id)
    {
        $request->validate([
            'proof_of_payment' => 'required|image|mimes:jpeg,png,jpg|max:5120',
        ]);

        $reservation = Reservation::findOrFail($id);

        if ($request->hasFile('proof_of_payment')) {
            if ($reservation->proof_of_payment_url) {
                $oldPath = str_replace('/storage/', '', parse_url($reservation->proof_of_payment_url, PHP_URL_PATH));
                Storage::disk('public')->delete($oldPath);
            }

            $path = $request->file('proof_of_payment')->store('proofs', 'public');
            
            $reservation->update([
                'proof_of_payment_url' => Storage::url($path),
                'status' => 'paid',
            ]);

            return back()->with('success', 'Bukti pembayaran berhasil diunggah! Menunggu konfirmasi admin.');
        }

        return back()->withErrors(['proof_of_payment' => 'Gagal mengunggah file.']);
    }

    /**
     * Menampilkan daftar semua reservasi di Dashboard Admin (Admin)
     */
    public function adminIndex()
    {
        $query = Reservation::with(['cafe', 'items']);
        
        if (auth()->user()->role === 'mitra') {
            $query->where('cafe_id', auth()->user()->owner_cafe);
        }

        $reservations = $query->latest()->paginate(10);

        return Inertia::render('admin/reservations/index', [
            'reservations' => $reservations
        ]);
    }

    /**
     * Mengubah status pesanan dari halaman Admin (Admin)
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,paid,completed,cancelled',
        ]);

        $reservation = Reservation::with(['cafe', 'items'])->findOrFail($id);
        $reservation->update([
            'status' => $request->status,
        ]);

        // Kirim email invoice ke customer saat admin menyetujui pembayaran
        if ($request->status === 'completed' && $reservation->customer_email) {
            Mail::to($reservation->customer_email)->send(new ReservationInvoiceMail($reservation));
        }

        return back()->with('success', 'Status reservasi berhasil diperbarui.');
    }

    /**
     * Menampilkan Rekap Laporan Keuangan Bulanan Mitra (Admin)
     */
    public function reports(Request $request)
    {
        // Ambil filter bulan dan tahun dari URL (default: bulan & tahun saat ini)
        $month = $request->input('month', date('m'));
        $year = $request->input('year', date('Y'));

        // Query: Ambil semua kafe, hitung jumlah transaksi dan total uangnya
        $query = Cafe::withCount(['reservations' => function ($q) use ($month, $year) {
                $q->whereIn('status', ['paid', 'completed'])
                      ->whereMonth('reservation_date', $month)
                      ->whereYear('reservation_date', $year);
            }])
            ->withSum(['reservations as total_revenue' => function ($q) use ($month, $year) {
                $q->whereIn('status', ['paid', 'completed'])
                      ->whereMonth('reservation_date', $month)
                      ->whereYear('reservation_date', $year);
            }], 'total_price');

        if (auth()->user()->role === 'mitra') {
            $query->where('id', auth()->user()->owner_cafe);
        }

        $reports = $query->get()
            ->map(function ($cafe) {
                // Perhitungan Model Bisnis (Platform Fee 10%)
                $gross = $cafe->total_revenue ?? 0;
                $platform_fee = $gross * 0.10; // Potongan 10%
                $net = $gross - $platform_fee; // Hak bersih kafe

                return [
                    'id' => $cafe->id,
                    'name' => $cafe->name,
                    'total_transactions' => $cafe->reservations_count,
                    'gross_revenue' => $gross,
                    'platform_fee' => $platform_fee,
                    'net_revenue' => $net,
                ];
            });

        $grandTotalGross = $reports->sum('gross_revenue');
        $grandTotalFee = $reports->sum('platform_fee');
        $grandTotalNet = $reports->sum('net_revenue');

        return Inertia::render('admin/reports/index', [
            'reports' => $reports,
            'summary' => [
                'gross' => $grandTotalGross,
                'fee' => $grandTotalFee,
                'net' => $grandTotalNet,
            ],
            'filters' => [
                'month' => $month,
                'year' => $year,
            ]
        ]);
    }
}