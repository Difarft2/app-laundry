<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id('id_setting');
            $table->string('nama_app', 150);
            $table->string('versi_app', 50);
            $table->string('logo_path', 500)->nullable();
            $table->string('nomor_laundry', 30);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
