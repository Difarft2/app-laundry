<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waktu_layanan', function (Blueprint $table) {
            $table->id('id_waktu_layanan');
            $table->string('nama_waktu', 100);
            $table->unsignedSmallInteger('estimasi_hari');
            $table->text('deskripsi')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waktu_layanan');
    }
};
