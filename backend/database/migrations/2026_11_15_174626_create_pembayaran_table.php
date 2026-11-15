<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pembayaran', function (Blueprint $table) {
            $table->id('id_pembayaran');

            $table->foreignId('id_pesanan')
                ->constrained('pesanan', 'id_pesanan')
                ->cascadeOnDelete();

            $table->foreignId('id_metode_pembayaran')
                ->constrained('metode_pembayaran', 'id_metode_pembayaran')
                ->restrictOnDelete();

            $table->foreignId('id_user')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('kode_pembayaran', 50)->unique();

            $table->decimal('jumlah_bayar', 12, 2);

            $table->enum('jenis_pembayaran', [
                'dp',
                'angsuran',
                'pelunasan',
            ]);

            $table->enum('status_pembayaran', [
                'berhasil',
                'menunggu',
                'dibatalkan',
            ])->default('berhasil');

            $table->dateTime('tanggal_pembayaran');
            $table->text('catatan')->nullable();

            $table->timestamps();

            $table->index('id_pesanan');
            $table->index('status_pembayaran');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pembayaran');
    }
};
