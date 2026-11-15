<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proses_laundry', function (Blueprint $table) {
            $table->id('id_proses_laundry');

            $table->foreignId('id_detail_pesanan')
                ->unique()
                ->constrained('detail_pesanan', 'id_detail_pesanan')
                ->cascadeOnDelete();

            $table->foreignId('id_user')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->enum('status_proses', [
                'menunggu',
                'pencucian',
                'pengeringan',
                'finishing',
                'selesai',
            ])->default('menunggu');

            $table->dateTime('mulai_proses')->nullable();
            $table->dateTime('selesai_proses')->nullable();
            $table->text('catatan')->nullable();

            $table->timestamps();

            $table->index('status_proses');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proses_laundry');
    }
};
