<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservationItem extends Model
{
    use HasFactory;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'price' => 'decimal:2',
        'quantity' => 'integer',
    ];

    // Relasi kembali ke induk reservasinya
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }
}