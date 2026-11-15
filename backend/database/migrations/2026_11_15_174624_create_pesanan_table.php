<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pesanan', function (Blueprint $table) {
            $table->id('id_pesanan');

            $table->foreignId('id_user')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('kode_pesanan', 50)->unique();
            $table->string('nama_pelanggan', 150);
            $table->string('no_hp', 20);
            $table->text('alamat')->nullable();

            $table->dateTime('tanggal_masuk');
            $table->dateTime('estimasi_selesai')->nullable();
            $table->dateTime('tanggal_selesai')->nullable();

            $table->enum('status_pesanan', [
                'menunggu',
                'diproses',
                'siap_diambil',
                'dalam_pengantaran',
                'selesai',
                'dibatalkan',
            ])->default('menunggu');

            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('diskon', 12, 2)->default(0);
            $table->decimal('total_harga', 12, 2)->default(0);

            $table->text('catatan')->nullable();

            $table->timestamps();

            $table->index('status_pesanan');
            $table->index('tanggal_masuk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pesanan');
    }
};
