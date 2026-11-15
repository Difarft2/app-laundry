<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('layanan', function (Blueprint $table) {
            $table->id('id_layanan');

            $table->foreignId('id_waktu_layanan')
                ->constrained('waktu_layanan', 'id_waktu_layanan')
                ->restrictOnDelete();

            $table->string('nama_layanan', 100);

            $table->enum('jenis_layanan', [
                'kiloan',
                'satuan',
            ]);

            $table->enum('jenis_finishing', [
                'lipat',
                'setrika',
                'tanpa_finishing',
            ])->default('lipat');

            $table->decimal('harga', 12, 2);
            $table->text('deskripsi')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('layanan');
    }
};
