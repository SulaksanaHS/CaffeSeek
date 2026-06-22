<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            // Menghubungkan ke tabel cafes, jika cafe dihapus maka data reservasi ikut terhapus
            $table->foreignId('cafe_id')->constrained('cafes')->onDelete('cascade');
            
            $table->string('customer_name');
            $table->string('customer_whatsapp');
            $table->date('reservation_date');
            
            // Status pembayaran menggunakan ENUM dengan default 'pending'
            $table->enum('status', ['pending', 'paid', 'completed', 'cancelled'])->default('pending');
            
            // Total harga menggunakan tipe decimal agar akurat untuk nominal rupiah
            $table->decimal('total_price', 12, 2)->default(0);
            
            // Kolom bukti transfer dibuat nullable karena saat pertama booking objek ini masih kosong
            $table->string('proof_of_payment_url')->nullable();
            
            $table->timestamps();
            $table->softDeletes(); // Mendukung soft deletes jika diperlukan di masa depan
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};