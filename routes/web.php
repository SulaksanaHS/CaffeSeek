<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CafeController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ReservationController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [CustomerController::class, 'index'])->name('home');
Route::get('/cafes/{id}', [CustomerController::class, 'show'])->name('customer.cafes.show');

// Route untuk Customer (Proses Booking & Pembayaran)
Route::post('/reservations', [ReservationController::class, 'store'])->name('reservations.store');
Route::get('/reservations/{id}/payment', [ReservationController::class, 'payment'])->name('reservations.payment');
Route::post('/reservations/{id}/upload-proof', [ReservationController::class, 'uploadProof'])->name('reservations.uploadProof');

Route::get('/pricing', function () {
    return Inertia::render('customer/pricing'); 
})->name('customer.pricing');

Route::prefix('admin')->middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::resource('cafes', CafeController::class);
    
    // Route untuk Admin (List Transaksi & Verifikasi)
    Route::get('reservations', [ReservationController::class, 'adminIndex'])->name('admin.reservations.index');
    Route::patch('reservations/{id}/update-status', [ReservationController::class, 'updateStatus'])->name('admin.reservations.updateStatus');
    
    // Route untuk Laporan Keuangan (Rekap Bulanan)
    Route::get('reports', [ReservationController::class, 'reports'])->name('admin.reports.index');
});

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';