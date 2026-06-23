<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Cafe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $isMitra = $user->role === 'mitra';
        $ownerCafeId = $user->owner_cafe;

        // 1. Ambil data cafe beserta relasinya
        $cafesQuery = Cafe::with([
            'menus',
            'tables',
            'photos' => function ($q) {
                $q->orderByDesc('is_primary');
            }
        ]);

        if ($isMitra) {
            $cafesQuery->where('id', $ownerCafeId);
        }
        
        $cafes = $cafesQuery->get();

        // 2. Data Statistik (Widgets)
        if ($isMitra) {
            // Stats untuk Mitra
            $totalReservations = DB::table('reservations')->where('cafe_id', $ownerCafeId)->count();
            $totalRevenue = DB::table('reservations')
                ->where('cafe_id', $ownerCafeId)
                ->whereIn('status', ['paid', 'completed'])
                ->sum('total_price');
            $netRevenue = $totalRevenue * 0.90; // 10% platform fee
            $upcomingReservations = DB::table('reservations')
                ->where('cafe_id', $ownerCafeId)
                ->whereIn('status', ['paid', 'completed'])
                ->whereDate('reservation_date', '>=', now()->toDateString())
                ->count();
                
            $stats = [
                'total_reservations' => $totalReservations,
                'net_revenue' => $netRevenue,
                'upcoming_reservations' => $upcomingReservations,
            ];
        } else {
            // Stats untuk Admin
            $totalCafes = DB::table('cafes')->count();
            $totalUsers = DB::table('users')->whereNull('deleted_at')->count();
            $totalTransactions = DB::table('reservations')->count();
            $grossRevenue = DB::table('reservations')->whereIn('status', ['paid', 'completed'])->sum('total_price');
            $platformRevenue = $grossRevenue * 0.10; // 10% admin fee

            $stats = [
                'total_cafes' => $totalCafes,
                'total_users' => $totalUsers,
                'total_transactions' => $totalTransactions,
                'platform_revenue' => $platformRevenue,
            ];
        }

        // 3. Query Chart Reservasi Bulanan (Kompatibel dengan MySQL dan SQLite)
        $driver = DB::connection()->getDriverName();
        $chartQuery = DB::table('reservations')->whereYear('reservation_date', now()->format('Y'));
        
        if ($isMitra) {
            $chartQuery->where('cafe_id', $ownerCafeId);
        }

        if ($driver === 'sqlite') {
            $chartData = $chartQuery->select(
                    DB::raw("CAST(strftime('%m', reservation_date) AS INTEGER) as month"),
                    DB::raw("COUNT(*) as total")
                )
                ->groupBy(DB::raw("strftime('%m', reservation_date)"))
                ->orderBy(DB::raw("strftime('%m', reservation_date)"), 'asc')
                ->get()
                ->map(function ($row) {
                    $monthNumber = (int) $row->month;
                    return [
                        'month' => date('M', mktime(0, 0, 0, $monthNumber, 1)),
                        'total' => $row->total,
                    ];
                });
        } else {
            $chartData = $chartQuery->select(
                    DB::raw("MONTH(reservation_date) as month"),
                    DB::raw("COUNT(*) as total")
                )
                ->groupBy(DB::raw("MONTH(reservation_date)"))
                ->orderBy(DB::raw("MONTH(reservation_date)"), 'asc')
                ->get()
                ->map(function ($row) {
                    $monthNumber = (int) $row->month;
                    return [
                        'month' => date('M', mktime(0, 0, 0, $monthNumber, 1)),
                        'total' => $row->total,
                    ];
                });
        }

        // 4. Render ke halaman dashboard admin
        return Inertia::render('admin/dashboard/index', [
            'cafes' => $cafes,
            'stats' => $stats,
            'chartData' => $chartData
        ]);
    }
}