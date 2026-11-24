<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Status `dalam_pengantaran` diganti oleh tugas antar-jemput.
     *
     * Pesanan tetap `siap_diambil` sampai kurir menyelesaikan pengantaran,
     * lalu otomatis menjadi `selesai`.
     */
    private const STATUSES = [
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

    private const STATUSES_WITH_LEGACY = [
        'menunggu',
        'dicuci',
        'dikeringkan',
        'dilipat',
        'disetrika',
        'diproses',
        'siap_diambil',
        'dalam_pengantaran',
        'selesai',
        'dibatalkan',
    ];

    public function up(): void
    {
        DB::table('pesanan')
            ->where('status_pesanan', 'dalam_pengantaran')
            ->update(['status_pesanan' => 'siap_diambil']);

        $this->updateEnum(self::STATUSES);
    }

    public function down(): void
    {
        $this->updateEnum(self::STATUSES_WITH_LEGACY);
    }

    /**
     * @param array<int, string> $statuses
     */
    private function updateEnum(array $statuses): void
    {
        $driver = Schema::getConnection()->getDriverName();

        // SQLite menyimpan enum sebagai teks, sehingga tidak perlu diubah.
        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        $values = collect($statuses)
            ->map(fn (string $status) => "'{$status}'")
            ->implode(', ');

        DB::statement(
            "ALTER TABLE `pesanan` MODIFY COLUMN `status_pesanan` "
            . "ENUM({$values}) NOT NULL DEFAULT 'menunggu'"
        );
    }
};
