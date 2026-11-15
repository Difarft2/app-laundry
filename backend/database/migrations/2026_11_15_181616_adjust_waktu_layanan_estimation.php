<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('waktu_layanan', function (Blueprint $table) {
            $table->dropColumn('estimasi_hari');
        });

        Schema::table('waktu_layanan', function (Blueprint $table) {
            $table->unsignedSmallInteger('estimasi_min_jam')
                ->after('nama_waktu');

            $table->unsignedSmallInteger('estimasi_max_jam')
                ->after('estimasi_min_jam');
        });

        Schema::table('layanan', function (Blueprint $table) {
            $table->unsignedBigInteger('id_waktu_layanan')
                ->nullable()
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('layanan', function (Blueprint $table) {
            $table->unsignedBigInteger('id_waktu_layanan')
                ->nullable(false)
                ->change();
        });

        Schema::table('waktu_layanan', function (Blueprint $table) {
            $table->dropColumn([
                'estimasi_min_jam',
                'estimasi_max_jam',
            ]);
        });

        Schema::table('waktu_layanan', function (Blueprint $table) {
            $table->unsignedSmallInteger('estimasi_hari')
                ->after('nama_waktu');
        });
    }
};
