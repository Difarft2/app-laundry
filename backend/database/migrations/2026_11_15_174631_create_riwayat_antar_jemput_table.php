<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('riwayat_antar_jemput', function (Blueprint $table) {
            $table->id('id_riwayat_antar_jemput');

            $table->foreignId('id_antar_jemput')
                ->constrained('antar_jemput', 'id_antar_jemput')
                ->cascadeOnDelete();

            $table->foreignId('id_user')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('status_sebelumnya', 50)->nullable();
            $table->string('status_selanjutnya', 50);
            $table->text('keterangan')->nullable();

            $table->dateTime('tanggal_perubahan');

            $table->timestamps();

            $table->index('tanggal_perubahan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_antar_jemput');
    }
};
