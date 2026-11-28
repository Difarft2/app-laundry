<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const STATUSES = [
        'menunggu',
        'diproses',
        'finishing',
        'siap_diambil',
        'selesai',
        'dibatalkan',
    ];

    private const LEGACY_STATUSES = [
        'menunggu',
        'dicuci',
        'dikeringkan',
        'dilipat',
        'disetrika',
        'diproses',
        'siap_diambil',
        'selesai',
        'dibatalkan',
    ];

    public function up(): void
    {
        DB::table('pesanan')
            ->whereIn('status_pesanan', ['dicuci', 'dikeringkan'])
            ->update(['status_pesanan' => 'diproses']);

        DB::table('pesanan')
            ->whereIn('status_pesanan', ['dilipat', 'disetrika'])
            ->update(['status_pesanan' => 'finishing']);

        Schema::table('pesanan', function (Blueprint $table) {
            $table->enum('status_pesanan', self::STATUSES)
                ->default('menunggu')
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('pesanan', function (Blueprint $table) {
            $table->enum('status_pesanan', self::LEGACY_STATUSES)
                ->default('menunggu')
                ->change();
        });
    }
};
