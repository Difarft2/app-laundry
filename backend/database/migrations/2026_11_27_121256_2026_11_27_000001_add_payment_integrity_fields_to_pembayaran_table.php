<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pembayaran', function (Blueprint $table) {
            // Idempotency key: UUID untuk mencegah duplicate request pembayaran
            $table->string('idempotency_key', 36)->nullable()->unique()->after('kode_pembayaran');

            // Hash payload untuk memastikan retry memakai payload yang sama.
            $table->string('payload_hash', 64)->nullable()->after('idempotency_key');

            // amount_received: uang tunai/pengiriman yang benar-benar diterima dari pelanggan
            $table->decimal('amount_received', 12, 2)->default(0)->after('payload_hash');

            // amount_applied: nominal yang dialokasikan untuk membayar tagihan
            $table->decimal('amount_applied', 12, 2)->default(0)->after('amount_received');

            // change_amount: uang kembalian (hanya untuk metode tunai, nol untuk transfer/QRIS)
            $table->decimal('change_amount', 12, 2)->default(0)->after('amount_applied');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pembayaran', function (Blueprint $table) {
            $table->dropColumn(['idempotency_key', 'payload_hash', 'amount_received', 'amount_applied', 'change_amount']);
        });
    }
};
