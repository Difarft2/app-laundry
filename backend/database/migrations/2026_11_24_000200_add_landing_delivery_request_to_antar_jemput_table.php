<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const STATUSES = [
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
        Schema::table('antar_jemput', function (Blueprint $table) {
            // Permintaan dari landing page belum memiliki pesanan laundry.
            $table->unsignedBigInteger('id_pesanan')->nullable()->change();

            $table->enum('sumber', ['internal', 'landing'])
                ->default('internal')
                ->after('id_pesanan');
        });

        $this->updateStatusEnum(self::STATUSES);
    }

    public function down(): void
    {
        $this->updateStatusEnum(array_values(array_filter(
            self::STATUSES,
            fn (string $status) => $status !== 'menunggu_verifikasi'
        )));

        // Permintaan landing tidak bisa dikembalikan ke skema lama yang mewajibkan pesanan.
        DB::table('antar_jemput')->where('sumber', 'landing')->delete();

        Schema::table('antar_jemput', function (Blueprint $table) {
            $table->dropColumn('sumber');
            $table->unsignedBigInteger('id_pesanan')->nullable(false)->change();
        });
    }

    /**
     * @param  list<string>  $statuses
     */
    private function updateStatusEnum(array $statuses): void
    {
        $values = collect($statuses)->map(fn (string $status) => "'{$status}'")->implode(', ');

        DB::statement(
            "ALTER TABLE antar_jemput MODIFY status_antar_jemput ENUM({$values}) NOT NULL DEFAULT 'menunggu'"
        );
    }
};
