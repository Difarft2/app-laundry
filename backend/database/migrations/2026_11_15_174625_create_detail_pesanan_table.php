<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detail_pesanan', function (Blueprint $table) {
            $table->id('id_detail_pesanan');

            $table->foreignId('id_pesanan')
                ->constrained('pesanan', 'id_pesanan')
                ->cascadeOnDelete();

            $table->foreignId('id_layanan')
                ->constrained('layanan', 'id_layanan')
                ->restrictOnDelete();

            $table->string('nama_layanan', 100);
            $table->decimal('harga_satuan', 12, 2);
            $table->decimal('jumlah', 10, 2);
            $table->string('satuan', 20);
            $table->decimal('subtotal', 12, 2);
            $table->text('catatan')->nullable();

            $table->timestamps();

            $table->index('id_pesanan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detail_pesanan');
    }
};
