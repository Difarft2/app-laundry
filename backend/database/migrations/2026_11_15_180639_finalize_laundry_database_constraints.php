<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Mencegah nama waktu layanan duplikat.
        Schema::table('waktu_layanan', function (Blueprint $table) {
            $table->unique(
                'nama_waktu',
                'waktu_layanan_nama_unique'
            );
        });

        // Satu pesanan hanya boleh memiliki
        // satu penjemputan dan satu pengantaran.
        Schema::table('antar_jemput', function (Blueprint $table) {
            $table->unique(
                ['id_pesanan', 'jenis_antar_jemput'],
                'antar_jemput_pesanan_jenis_unique'
            );
        });

        // Tanggal pembayaran boleh kosong
        // selama transaksi belum berhasil.
        Schema::table('pembayaran', function (Blueprint $table) {
            $table->dateTime('tanggal_pembayaran')
                ->nullable()
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('pembayaran', function (Blueprint $table) {
            $table->dateTime('tanggal_pembayaran')
                ->nullable(false)
                ->change();
        });

        Schema::table('antar_jemput', function (Blueprint $table) {
            $table->dropUnique(
                'antar_jemput_pesanan_jenis_unique'
            );
        });

        Schema::table('waktu_layanan', function (Blueprint $table) {
            $table->dropUnique(
                'waktu_layanan_nama_unique'
            );
        });
    }
};
