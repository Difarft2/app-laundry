<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('antar_jemput', function (Blueprint $table) {
            $table->id('id_antar_jemput');

            $table->foreignId('id_pesanan')
                ->constrained('pesanan', 'id_pesanan')
                ->cascadeOnDelete();

            $table->foreignId('id_kurir')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->enum('jenis_antar_jemput', [
                'penjemputan',
                'pengantaran',
            ]);

            $table->enum('status_antar_jemput', [
                'menunggu',
                'ditugaskan',
                'menuju_lokasi',
                'barang_diambil',
                'barang_diantar',
                'selesai',
                'gagal',
            ])->default('menunggu');

            $table->text('alamat_tujuan');
            $table->string('nama_penerima', 150)->nullable();
            $table->string('no_hp_penerima', 20)->nullable();

            $table->dateTime('jadwal')->nullable();
            $table->dateTime('tanggal_selesai')->nullable();

            $table->text('catatan')->nullable();

            $table->timestamps();

            $table->index('status_antar_jemput');
            $table->index('jenis_antar_jemput');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('antar_jemput');
    }
};
