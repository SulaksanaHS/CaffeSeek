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
        Schema::create('reservation_items', function (Blueprint $table) {
            $table->id();
            // Menghubungkan ke tabel utama reservations, jika reservasi dihapus maka item di dalamnya ikut terhapus
            $table->foreignId('reservation_id')->constrained('reservations')->onDelete('cascade');
            
            // Menyimpan jenis item: 'menu', 'table', atau 'meeting_room'
            $table->string('item_type'); 
            
            $table->string('name');
            $table->decimal('price', 12, 2)->default(0);
            $table->integer('quantity')->default(1);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservation_items');
    }
};