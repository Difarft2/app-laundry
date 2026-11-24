<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

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
        $values = collect($statuses)->map(fn (string $status) => "'{$status}'")->implode(', ');

        DB::statement(
            "ALTER TABLE antar_jemput MODIFY status_antar_jemput ENUM({$values}) NOT NULL DEFAULT 'menunggu'"
        );
    }
};
