<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const WITH_JOURNEY = [
        'menunggu_verifikasi',
        'menunggu',
        'ditugaskan',
        'menuju_lokasi',
        'menuju_laundry',
        'pakaian_sampai_laundry',
        'barang_diambil',
        'barang_diantar',
        'selesai',
        'gagal',
    ];

    private const WITHOUT_JOURNEY = [
        'menunggu_verifikasi',
        'menunggu',
        'ditugaskan',
        'menuju_lokasi',
        'barang_diambil',
        'barang_diantar',
        'selesai',
        'gagal',
    ];

    public function up(): void
    {
        $this->setStatuses(self::WITH_JOURNEY);
    }

    public function down(): void
    {
        DB::table('antar_jemput')
            ->whereIn('status_antar_jemput', ['menuju_laundry', 'pakaian_sampai_laundry'])
            ->update(['status_antar_jemput' => 'barang_diambil']);

        $this->setStatuses(self::WITHOUT_JOURNEY);
    }

    /**
     * @param  list<string>  $statuses
     */
    private function setStatuses(array $statuses): void
    {
        Schema::table('antar_jemput', function (Blueprint $table) use ($statuses) {
            $table->enum('status_antar_jemput', $statuses)->default('menunggu')->change();
        });
    }
};
