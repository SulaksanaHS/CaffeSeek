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
        // 1. Ambil data cafe beserta relasinya (Tetap dipertahankan sesuai kodemu)
        $cafes = Cafe::with([
            'menus',
            'tables',
            'photos' => function ($q) {
                $q->orderByDesc('is_primary');
            }
        ])->get();

        // 2. Query Chart yang sudah diperbaiki untuk MySQL (Laragon)
        $chartData = Cafe::select(
                DB::raw("MONTH(created_at) as month"),
                DB::raw("COUNT(*) as total")
            )
            ->whereRaw("YEAR(created_at) = ?", [now()->format('Y')])
            ->groupBy(DB::raw("MONTH(created_at)"))
            ->orderBy(DB::raw("MONTH(created_at)"), 'asc')
            ->get()
            ->map(function($row) {
                // Konversi angka bulan (1-12) menjadi singkatan nama bulan (Jan, Feb, Mar...)
                $monthNumber = (int) $row->month;
                return [
                    'month' => date('M', mktime(0, 0, 0, $monthNumber, 1)),
                    'total' => $row->total
                ];
            });

        // 3. Render ke halaman dashboard admin
        return Inertia::render('admin/dashboard/index', [
            'cafes' => $cafes,
            'chartData' => $chartData
        ]);
    }
}