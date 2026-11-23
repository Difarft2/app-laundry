<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tahapan proses laundry yang diikuti dari form scan QR karyawan.
     *
     * `menunggu` tetap dipakai sebagai "Menunggu giliran", sehingga status
     * lama tidak perlu diubah. Status pengantaran dipertahankan agar alur
     * antar-jemput yang sudah ada tidak rusak.
     */
    private const STATUSES = [
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

    private const LEGACY_STATUSES = [
        'menunggu',
        'diproses',
        'siap_diambil',
        'dalam_pengantaran',
        'selesai',
        'dibatalkan',
    ];

    public function up(): void
    {
        $this->updateEnum(self::STATUSES);
    }

    public function down(): void
    {
        // Sebelum menghapus status baru, kembalikan data yang masih
        // menggunakan tahapan laundry ke status lama agar aman.
        DB::table('pesanan')
            ->whereIn('status_pesanan', ['dicuci', 'dikeringkan', 'dilipat', 'disetrika'])
            ->update(['status_pesanan' => 'diproses']);

        $this->updateEnum(self::LEGACY_STATUSES);
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
