<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reservation extends Model
{
    use HasFactory, SoftDeletes;

    // Mengizinkan semua kolom diisi massal kecuali id, created_at, updated_at
    protected $guarded = ['id', 'created_at', 'updated_at'];

    // Memastikan format data keluar-masuk sudah rapi
    protected $casts = [
        'reservation_date' => 'date',
        'total_price' => 'decimal:2',
    ];

    // Relasi kembali ke Kafe (Satu reservasi punya satu kafe)
    public function cafe(): BelongsTo
    {
        return $this->belongsTo(Cafe::class);
    }

    // Relasi ke detail pesanan (Satu reservasi bisa punya banyak menu/meja)
    public function items(): HasMany
    {
        return $this->hasMany(ReservationItem::class);
    }
}