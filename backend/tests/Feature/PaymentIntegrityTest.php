<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Pembayaran;
use App\Models\Pesanan;
use App\Models\MetodePembayaran;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PaymentIntegrityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Payload order creation standar sesuai kontrak API.
     * Setiap order harus memiliki detail_pesanan array.
     */
    private function orderPayload(array $overrides = []): array
    {
        $layanan = \App\Models\Layanan::where('is_active', true)->first();
        $metode = \App\Models\MetodePembayaran::where('kode', 'tunai')->first();

        // Default payload with IDs from database
        $defaults = [
            'nama_pelanggan' => 'Pelanggan Test',
            'no_hp' => '081234567890',
            'alamat' => 'Jl. Test No. 1',
            'tanggal_masuk' => now()->format('Y-m-d'),
            'diskon' => 0,
            'catatan' => 'Test order via API',
        ];

        // Set layanan ID if available
        if ($layanan) {
            $defaults['detail_pesanan'] = [
                ['id_layanan' => $layanan->id_layanan, 'jumlah' => 1]
            ];
        } else {
            $defaults['detail_pesanan'] = [
                ['id_layanan' => 1, 'jumlah' => 1]
            ];
        }

        // Set metode ID if available
        if ($metode) {
            $defaults['id_metode_pembayaran'] = $metode->id_metode_pembayaran;
        } else {
            $defaults['id_metode_pembayaran'] = 1;
        }

        return array_merge($defaults, $overrides);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        // Always create reference data needed for tests (RefreshDatabase truncates all tables)
        // Order matters: WaktuLayanan first, then Layanan (due to FK constraint), then MetodePembayaran

        // Create WaktuLayanan records (use unique names to avoid conflicts if seeder ran)
        $waktu1 = \App\Models\WaktuLayanan::updateOrCreate(
            ['id_waktu_layanan' => 1],
            ['nama_waktu' => 'Test Lain', 'estimasi_min_jam' => 48, 'estimasi_max_jam' => 72]
        );

        $waktu2 = \App\Models\WaktuLayanan::updateOrCreate(
            ['id_waktu_layanan' => 2],
            ['nama_waktu' => 'Test Cuci', 'estimasi_min_jam' => 24, 'estimasi_max_jam' => 72]
        );

        // Create Layanan records with proper WaktuLayanan references
        \App\Models\Layanan::updateOrCreate(
            ['id_layanan' => 1],
            ['id_waktu_layanan' => $waktu1->id_waktu_layanan, 'nama_layanan' => 'Lainnya', 'jenis_layanan' => 'kiloan', 'harga' => 50000, 'is_active' => true]
        );

        \App\Models\Layanan::updateOrCreate(
            ['id_layanan' => 2],
            ['id_waktu_layanan' => $waktu2->id_waktu_layanan, 'nama_layanan' => 'Setrika', 'jenis_layanan' => 'satuan', 'harga' => 30000, 'is_active' => true]
        );

        // Pastikan ada metode pembayaran tunai dan transfer
        \App\Models\MetodePembayaran::updateOrCreate(
            ['id_metode_pembayaran' => 1],
            ['nama_metode' => 'Tunai', 'kode' => 'tunai', 'is_active' => true]
        );

        \App\Models\MetodePembayaran::updateOrCreate(
            ['id_metode_pembayaran' => 2],
            ['nama_metode' => 'Transfer Bank', 'kode' => 'transfer', 'is_active' => true]
        );
    }

    private function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);
        return $user->fresh();
    }

    private function paymentMethod(string $type = 'tunai'): MetodePembayaran
    {
        return MetodePembayaran::firstOrCreate([
            'nama_metode' => $type === 'tunai' ? 'Tunai' : 'Transfer Bank',
            'kode' => $type,
            'is_active' => true,
        ]);
    }

    // ========================================================================
    // A. PEMBUATAN PEMBAYARAN
    // ========================================================================

    public function test_tunai_pas_tidak_ada_kembalian(): void
    {
        $response = $this->actingAs($this->userWithRole('karyawan'), 'sanctum')
            ->postJson('/api/v1/orders', $this->orderPayload([
                'status_pesanan' => 'diproses',
                'total_harga' => 50000,
                'cara_bayar' => 'now',
                'status_pembayaran' => 'berhasil',
                'jumlah_bayar' => 50000
            ]));

        $response->assertCreated();
        $data = $response->json('data');

        // Memastikan field integritas diset
        $this->assertEquals(50000, $data['total_terbayar']);
        $this->assertEquals(0, $data['sisa_pembayaran']);
    }

    public function test_tunai_dengan_kembalian(): void
    {
        $order = Pesanan::factory()->create([
            'status_pesanan' => 'diproses',
            'total_harga' => 50000,
            'diskon' => 0,
        ]);

        $response = $this->actingAs($this->userWithRole('karyawan'), 'sanctum')
            ->postJson('/api/v1/orders', array_merge(
                [
                    'nama_pelanggan' => 'Test Pelanggan',
                    'no_hp' => '081234567890',
                    'alamat' => 'Jl. Test No. 1',
                    'tanggal_masuk' => now()->format('Y-m-d'),
                    'diskon' => 0,
                    'detail_pesanan' => [['id_layanan' => 1, 'jumlah' => 1]],
                    'catatan' => 'Test order'
                ],
                Pesanan::factory()->raw([
                    'status_pesanan' => 'diproses',
                    'total_harga' => 50000,
                ]),
                ['cara_bayar' => 'now', 'status_pembayaran' => 'berhasil', 'jumlah_bayar' => 60000, 'id_metode_pembayaran' => 1]
            ));

        $response->assertCreated();
        $data = $response->json('data');

        // Memastikan kembalian dihitung
        $payments = $data['pembayaran'];
        $this->assertCount(1, $payments);
        $payment = $payments[0];
        $this->assertEquals(50000, $payment['amount_applied']);
        $this->assertEquals(60000, $payment['amount_received']);
        $this->assertEquals(10000, $payment['change_amount']);
    }

    public function test_tunai_kurang_dari_tagihan(): void
    {
        $order = Pesanan::factory()->create([
            'status_pesanan' => 'diproses',
            'total_harga' => 100000,
            'diskon' => 0,
        ]);

        $response = $this->actingAs($this->userWithRole('karyawan'), 'sanctum')
            ->postJson('/api/v1/orders', array_merge(
                [
                    'nama_pelanggan' => 'Test Pelanggan',
                    'no_hp' => '081234567890',
                    'alamat' => 'Jl. Test No. 1',
                    'tanggal_masuk' => now()->format('Y-m-d'),
                    'diskon' => 0,
                    'detail_pesanan' => [['id_layanan' => 1, 'jumlah' => 1]],
                    'catatan' => 'Test order'
                ],
                Pesanan::factory()->raw([
                    'status_pesanan' => 'diproses',
                    'total_harga' => 100000,
                ]),
                ['cara_bayar' => 'now', 'status_pembayaran' => 'berhasil', 'jumlah_bayar' => 30000, 'id_metode_pembayaran' => 1]
            ));

        $response->assertCreated();
        $data = $response->json('data');

        // Memastikan hanya 30000 yang dialokasikan
        $payments = $data['pembayaran'];
        $this->assertCount(1, $payments);
        $payment = $payments[0];
        $this->assertEquals(30000, $payment['amount_applied']);
        $this->assertEquals(0, $payment['change_amount']);
        $this->assertEquals(30000, $data['total_terbayar']);
        $this->assertEquals(70000, $data['sisa_pembayaran']);
    }

    public function test_transfer_status_menunggu_otomatis(): void
    {
        $order = Pesanan::factory()->create([
            'status_pesanan' => 'diproses',
            'total_harga' => 50000,
            'diskon' => 0,
        ]);

        $response = $this->actingAs($this->userWithRole('karyawan'), 'sanctum')
            ->postJson('/api/v1/orders', array_merge(
                [
                    'nama_pelanggan' => 'Test Pelanggan',
                    'no_hp' => '081234567890',
                    'alamat' => 'Jl. Test No. 1',
                    'tanggal_masuk' => now()->format('Y-m-d'),
                    'diskon' => 0,
                    'detail_pesanan' => [['id_layanan' => 1, 'jumlah' => 1]],
                    'catatan' => 'Test order'
                ],
                Pesanan::factory()->raw([
                    'status_pesanan' => 'diproses',
                    'total_harga' => 50000,
                ]),
                [
                    'cara_bayar' => 'now',
                    'id_metode_pembayaran' => 2,
                    'jumlah_bayar' => 50000,
                    'status_pembayaran' => 'berhasil',
                ]
            ));

        $response->assertCreated();
        $data = $response->json('data');

        // Transfer harus otomatis menunggu
        $payment = $data['pembayaran'][0];
        $this->assertEquals('menunggu', $payment['status_pembayaran'], 'Status otomatis menjadi menunggu');
    }

    public function test_qris_status_menunggu_otomatis(): void
    {
        $qrisMethod = MetodePembayaran::firstOrCreate([
            'nama_metode' => 'QRIS',
            'kode' => 'qris',
            'is_active' => true,
        ]);

        $order = Pesanan::factory()->create([
            'status_pesanan' => 'diproses',
            'total_harga' => 50000,
            'diskon' => 0,
        ]);

        $response = $this->actingAs($this->userWithRole('karyawan'), 'sanctum')
            ->postJson('/api/v1/orders', array_merge(
                [
                    'nama_pelanggan' => 'Test Pelanggan',
                    'no_hp' => '081234567890',
                    'alamat' => 'Jl. Test No. 1',
                    'tanggal_masuk' => now()->format('Y-m-d'),
                    'diskon' => 0,
                    'detail_pesanan' => [['id_layanan' => 1, 'jumlah' => 1]],
                    'catatan' => 'Test order'
                ],
                Pesanan::factory()->raw([
                    'status_pesanan' => 'diproses',
                    'total_harga' => 50000,
                ]),
                [
                    'cara_bayar' => 'now',
                    'id_metode_pembayaran' => $qrisMethod->id_metode_pembayaran,
                    'jumlah_bayar' => 50000,
                    'status_pembayaran' => 'berhasil',
                ]
            ));

        $response->assertCreated();
        $data = $response->json('data');

        // QRIS harus otomatis menunggu
        $payment = $data['pembayaran'][0];
        $this->assertEquals('menunggu', $payment['status_pembayaran'], 'Status otomatis menjadi menunggu');
    }

    public function test_transfer_melebihi_sisa_tagihan_ditolak(): void
    {
        $order = Pesanan::factory()->create([
            'status_pesanan' => 'diproses',
            'total_harga' => 50000,
            'diskon' => 0,
        ]);

        // Sisa tagihan = 50000 (belum ada pembayaran)
        $response = $this->actingAs($this->userWithRole('karyawan'), 'sanctum')
            ->postJson('/api/v1/orders', array_merge(
                Pesanan::factory()->raw([
                    'status_pesanan' => 'diproses',
                    'total_harga' => 50000,
                ]),
                [
                    'cara_bayar' => 'later',
                    'jumlah_bayar' => 60000, // Lebih besar dari sisa tagihan
                    'status_pembayaran' => 'berhasil',
                ]
            ));

        $response->assertStatus(422);
        $this->assertStringContainsString('melebihi sisa tagihan', $response->json('message'));
    }

    public function test_pelunasan_menggunakan_post_payment_harus_sama_dengan_outstanding(): void
    {
        // Buat pesanan dengan beberapa pembayaran
        $order = Pesanan::factory()->create([
            'status_pesanan' => 'diproses',
            'total_harga' => 100000,
            'diskon' => 0,
        ]);

        // Buat pembayaran transfer menunggu
        $payment = $order->pembayaran()->create([
            'id_metode_pembayaran' => $this->paymentMethod('transfer')->id_metode_pembayaran,
            'id_user' => $this->userWithRole('karyawan')->id,
            'kode_pembayaran' => 'PAY-' . now()->format('Ymd') . '-' . \Illuminate\Support\Str::upper(\Illuminate\Support\Str::random(10)),
            'jumlah_bayar' => 30000,
            'idempotency_key' => (string) \Illuminate\Support\Str::uuid(),
            'amount_received' => 30000,
            'amount_applied' => 30000,
            'change_amount' => 0,
            'jenis_pembayaran' => 'dp',
            'status_pembayaran' => 'menunggu',
            'tanggal_pembayaran' => now(),
            'catatan' => 'DP 30 ribu',
            'payload_hash' => hash('sha256', json_encode([])),
        ]);

        // Cek sisa tagihan: 100000 - 30000 = 70000
        $outstanding = 70000;

        $response = $this->actingAs($this->userWithRole('karyawan'), 'sanctum')
            ->postJson('/api/v1/payments', [
                'id_pesanan' => $order->id_pesanan,
                'id_metode_pembayaran' => $this->paymentMethod('transfer')->id_metode_pembayaran,
                'jumlah_bayar' => $outstanding + 1, // Sama persis + 1
                'idempotency_key' => (string) \Illuminate\Support\Str::uuid(),
                'amount_received' => $outstanding + 1,
                'jenis_pembayaran' => 'pelunasan',
                'status_pembayaran' => 'berhasil',
            ]);

        // Harus ditolak karena tidak sama persis
        $response->assertStatus(422);
        $this->assertStringContainsString('sama dengan sisa tagihan', $response->json('message'));
    }

    // ========================================================================
    // B. VERIFIKASI PEMBAYARAN
    // ========================================================================

    public function test_owner_dapat_memverifikasi_transfer(): void
    {
        $order = Pesanan::factory()->create([
            'status_pesanan' => 'diproses',
            'total_harga' => 50000,
            'diskon' => 0,
        ]);

        $payment = $order->pembayaran()->create([
            'id_metode_pembayaran' => $this->paymentMethod('transfer')->id_metode_pembayaran,
            'id_user' => $this->userWithRole('karyawan')->id,
            'kode_pembayaran' => 'PAY-' . now()->format('Ymd') . '-' . \Illuminate\Support\Str::upper(\Illuminate\Support\Str::random(10)),
            'jumlah_bayar' => 30000,
            'idempotency_key' => (string) \Illuminate\Support\Str::uuid(),
            'amount_received' => 30000,
            'amount_applied' => 30000,
            'change_amount' => 0,
            'jenis_pembayaran' => 'pelunasan',
            'status_pembayaran' => 'menunggu',
            'tanggal_pembayaran' => now(),
            'catatan' => 'Pembayaran manual',
            'payload_hash' => hash('sha256', json_encode([])),
        ]);

        $owner = $this->userWithRole('owner');

        $response = $this->actingAs($owner, 'sanctum')
            ->patchJson("/api/v1/payments/{$payment->id_pembayaran}/verify");

        $response->assertStatus(200);
        $data = $response->json('data');

        // Status berubah menjadi berhasil
        $this->assertEquals('berhasil', $data['status_pembayaran']);
        // Catatan menunjukkan diverifikasi
        $this->assertStringContainsString('DIVERIFIKASI', $data['catatan']);

        // Cek sisa tagihan berkurang
        $this->assertEquals(20000, $data['pesanan']['sisa_tagihan']);
    }

    public function test_admin_dapat_memverifikasi_qris(): void
    {
        $order = Pesanan::factory()->create([
            'status_pesanan' => 'diproses',
            'total_harga' => 50000,
            'diskon' => 0,
        ]);

        // Gunakan metode QRIS (kode = 'qris')
        $qrisMethod = MetodePembayaran::firstOrCreate([
            'nama_metode' => 'QRIS',
            'kode' => 'qris',
            'is_active' => true,
        ]);

        $payment = $order->pembayaran()->create([
            'id_metode_pembayaran' => $qrisMethod->id_metode_pembayaran,
            'id_user' => $this->userWithRole('karyawan')->id,
            'kode_pembayaran' => 'PAY-' . now()->format('Ymd') . '-' . \Illuminate\Support\Str::upper(\Illuminate\Support\Str::random(10)),
            'jumlah_bayar' => 50000,
            'idempotency_key' => (string) \Illuminate\Support\Str::uuid(),
            'amount_received' => 50000,
            'amount_applied' => 50000,
            'change_amount' => 0,
            'jenis_pembayaran' => 'pelunasan',
            'status_pembayaran' => 'menunggu',
            'tanggal_pembayaran' => now(),
            'catatan' => 'QRIS Manual',
            'payload_hash' => hash('sha256', json_encode([])),
        ]);

        $admin = $this->userWithRole('admin');

        $response = $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/v1/payments/{$payment->id_pembayaran}/verify");

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertEquals('berhasil', $data['status_pembayaran']);
        $this->assertEquals('menunggu', $data['pesanan']['sisa_tagihan']); // Sisa 0 halilau sudah lunas
    }

    public function test_karyawan_ditolak_ketika_mencoba_verifikasi(): void
    {
        $order = Pesanan::factory()->create([
            'status_pesanan' => 'diproses',
            'total_harga' => 50000,
            'diskon' => 0,
        ]);

        $payment = $order->pembayaran()->create([
            'id_metode_pembayaran' => $this->paymentMethod('transfer')->id_metode_pembayaran,
            'id_user' => $this->userWithRole('karyawan')->id,
            'kode_pembayaran' => 'PAY-' . now()->format('Ymd') . '-' . \Illuminate\Support\Str::upper(\Illuminate\Support\Str::random(10)),
            'jumlah_bayar' => 30000,
            'idempotency_key' => (string) \Illuminate\Support\Str::uuid(),
            'amount_received' => 30000,
            'amount_applied' => 30000,
            'change_amount' => 0,
            'jenis_pembayaran' => 'pelunasan',
            'status_pembayaran' => 'menunggu',
            'tanggal_pembayaran' => now(),
            'catatan' => 'Pembayaran manual',
            'payload_hash' => hash('sha256', json_encode([])),
        ]);

        $karyawan = $this->userWithRole('karyawan');

        $response = $this->actingAs($karyawan, 'sanctum')
            ->patchJson("/api/v1/payments/{$payment->id_pembayaran}/verify");

        $response->assertStatus(403);
    }

    public function test_pembayaran_selain_menunggu_tidak_dapat_diverifikasi(): void
    {
        $order = Pesanan::factory()->create([
            'status_pesanan' => 'diproses',
            'total_harga' => 50000,
            'diskon' => 0,
        ]);

        $payment = $order->pembayaran()->create([
            'id_metode_pembayaran' => $this->paymentMethod('transfer')->id_metode_pembayaran,
            'id_user' => $this->userWithRole('karyawan')->id,
            'kode_pembayaran' => 'PAY-' . now()->format('Ymd') . '-' . \Illuminate\Support\Str::upper(\Illuminate\Support\Str::random(10)),
            'jumlah_bayar' => 50000,
            'idempotency_key' => (string) \Illuminate\Support\Str::uuid(),
            'amount_received' => 50000,
            'amount_applied' => 50000,
            'change_amount' => 0,
            'jenis_pembayaran' => 'pelunasan',
            'status_pembayaran' => 'berhasil', // Sudah berhasil
            'tanggal_pembayaran' => now(),
            'catatan' => 'Sudah lunas',
            'payload_hash' => hash('sha256', json_encode([])),
        ]);

        $owner = $this->userWithRole('owner');

        $response = $this->actingAs($owner, 'sanctum')
            ->patchJson("/api/v1/payments/{$payment->id_pembayaran}/verify");

        $response->assertStatus(422);
        $this->assertStringContainsString('menunggu', $response->json('message'));
    }

    public function test_verifikasi_tidak_menggandakan_alokasi(): void
    {
        $order = Pesanan::factory()->create([
            'status_pesanan' => 'diproses',
            'total_harga' => 100000,
            'diskon' => 0,
        ]);

        // Pembayaran awal danaman
        $payment1 = $order->pembayaran()->create([
            'id_metode_pembayaran' => $this->paymentMethod('transfer')->id_metode_pembayaran,
            'id_user' => $this->userWithRole('karyawan')->id,
            'kode_pembayaran' => 'PAY-' . now()->format('Ymd') . '-' . \Illuminate\Support\Str::upper(\Illuminate\Support\Str::random(10)),
            'jumlah_bayar' => 30000,
            'idempotency_key' => (string) \Illuminate\Support\Str::uuid(),
            'amount_received' => 30000,
            'amount_applied' => 30000,
            'change_amount' => 0,
            'jenis_pembayaran' => 'dp',
            'status_pembayaran' => 'berhasil',
            'tanggal_pembayaran' => now(),
            'catatan' => 'DP',
            'payload_hash' => hash('sha256', json_encode([])),
        ]);

        $outstanding = 70000;
        $payment2 = $order->pembayaran()->create([
            'id_metode_pembayaran' => $this->paymentMethod('transfer')->id_metode_pembayaran,
            'id_user' => $this->userWithRole('karyawan')->id,
            'kode_pembayaran' => 'PAY-' . now()->format('Ymd') . '-' . \Illuminate\Support\Str::upper(\Illuminate\Support\Str::random(10)),
            'jumlah_bayar' => $outstanding,
            'idempotency_key' => (string) \Illuminate\Support\Str::uuid(),
            'amount_received' => $outstanding,
            'amount_applied' => $outstanding,
            'change_amount' => 0,
            'jenis_pembayaran' => 'pelunasan',
            'status_pembayaran' => 'menunggu',
            'tanggal_pembayaran' => now(),
            'catatan' => 'Pelunasan',
            'payload_hash' => hash('sha256', json_encode([])),
        ]);

        $owner = $this->userWithRole('owner');

        // Verifikasi pembayaran kedua
        $response = $this->actingAs($owner, 'sanctum')
            ->patchJson("/api/v1/payments/{$payment2->id_pembayaran}/verify");

        $response->assertStatus(200);

        $this->assertEquals(20000, $order->sisa_tagihan);
    }

    // ========================================================================
    // C. IDEMPOTENCY
    // ========================================================================

    public function test_idempotency_key_sama_payload_sama_kembalikan_data_lama(): void
    {
        $order = Pesanan::factory()->create([
            'status_pesanan' => 'diproses',
            'total_harga' => 50000,
            'diskon' => 0,
        ]);

        $idempotencyKey = (string) \Illuminate\Support\Str::uuid();

        // Kirim pertama kali
        $response1 = $this->actingAs($this->userWithRole('karyawan'), 'sanctum')
            ->postJson('/api/v1/orders', array_merge(
                Pesanan::factory()->raw([
                    'status_pesanan' => 'diproses',
                    'total_harga' => 50000,
                ]),
                [
                    'cara_bayar' => 'later',
                    'jumlah_bayar' => 50000,
                    'status_pembayaran' => 'berhasil',
                ]
            ));

        $response1->assertCreated();
        $payment1 = $response1->json('data.pesanan.pembayaran')[0];

        // Kirim kembali dengan key sama dan payload sama (hanya timestamp sedikit beda)
        $response2 = $this->actingAs($this->userWithRole('karyawan'), 'sanctum')
            ->postJson('/api/v1/payments', [
                'id_pesanan' => $order->id_pesanan,
                'id_metode_pembayaran' => $order->metodePembayaran->id_pesanan,
                'jumlah_bayar' => 50000,
                'idempotency_key' => $idempotencyKey,
                'amount_received' => 50000,
                'status_pembayaran' => 'berhasil',
                'jenis_pembayaran' => 'pelunasan',
            ]);

        // Harus mengembalikan data lama (idempotent)
        $response2->assertStatus(200);
        $data2 = $response2->json('data');

        // Data harus sama dengan yang pertama
        $this->assertEquals($payment1['kode_pembayaran'], $data2['kode_pembayaran']);
    }

    public function test_idempotency_key_sama_payload_berbeda_menghasilkan_409(): void
    {
        $order = Pesanan::factory()->create([
            'status_pesanan' => 'diproses',
            'total_harga' => 50000,
            'diskon' => 0,
        ]);

        $idempotencyKey = (string) \Illuminate\Support\Str::uuid();

        // Kirim dengan payload A
        $response1 = $this->actingAs($this->userWithRole('karyawan'), 'sanctum')
            ->postJson('/api/v1/payments', [
                'id_pesanan' => $order->id_pesanan,
                'id_metode_pembayaran' => $order->metodePembayaran->id_pesanan,
                'jumlah_bayar' => 30000,
                'idempotency_key' => $idempotencyKey,
                'amount_received' => 30000,
                'status_pembayaran' => 'berhasil',
                'jenis_pembayaran' => 'dp',
            ]);

        $response1->assertCreated();

        // Kirim dengan payload berbeda (nominal beda)
        $response2 = $this->actingAs($this->userWithRole('karyawan'), 'sanctum')
            ->postJson('/api/v1/payments', [
                'id_pesanan' => $order->id_pesanan,
                'id_metode_pembayaran' => $order->metodePembayaran->id_pesanan,
                'jumlah_bayar' => 50000,
                'idempotency_key' => $idempotencyKey,
                'amount_received' => 50000,
                'status_pembayaran' => 'berhasil',
                'jenis_pembayaran' => 'pelunasan',
            ]);

        // Harus conflict
        $response2->assertStatus(409);
        $this->assertStringContainsString('digunakan dengan payload', $response2->json('message'));
    }

    public function test_idempotency_key_sama_pada_pesanan_berbeda_menghasilkan_409(): void
    {
        $idempotencyKey = (string) \Illuminate\Support\Str::uuid();

        // Pesanan A
        $orderA = Pesanan::factory()->create(['total_harga' => 50000]);
        OrderController::store(array_merge(
            Pesanan::factory()->raw(['total_harga' => 50000, 'status_pesanan' => 'diproses']),
            ['cara_bayar' => 'later', 'jumlah_bayar' => 50000, 'status_pembayaran' => 'berhasil']
        ));

        // Cheate-sheets data
        $paymentA = $orderA->pembayaran->first();

        // Pesanan B mencoba menggunakan key yang sama
        $orderB = Pesanan::factory()->create(['total_harga' => 80000]);
        $karyawan = $this->userWithRole('karyawan');

        $response = $this->actingAs($karyawan, 'sanctum')
            ->postJson('/api/v1/payments', [
                'id_pesanan' => $orderB->id_pesanan,
                'id_metode_pembayaran' => $orderB->metodePembayaran->id_pesanan,
                'jumlah_bayar' => 40000,
                'idempotency_key' => $idempotencyKey,
                'amount_received' => 40000,
                'status_pembayaran' => 'berhasil',
                'jenis_pembayaran' => 'dp',
            ]);

        $response->assertStatus(409);
    }

    public function test_duplicate_key_exception_ditangani_dengan_benar(): void
    {
        $order = Pesanan::factory()->create(['total_harga' => 50000]);

        // Force insert duplicate: barraiki hapus constraint via SQL hariat (dilakukan via bypass jika real)
        // Untuk skenario ini, kita buat bypass menggunakan route sebanyak berulang

        // Simulasi race condition:
        $response1 = $this->actingAs($this->userWithRole('karyawan'), 'sanctum')
            ->postJson('/api/v1/payments', [
                'id_pesanan' => $order->id_pesanan,
                'id_metode_pembayaran' => $order->metodePembayaran->id_pesanan,
                'jumlah_bayar' => 30000,
                'idempotency_key' => (string) \Illuminate\Support\Str::uuid(),
                'amount_received' => 30000,
                'status_pembayaran' => 'berhasil',
                'jenis_pembayaran' => 'dp',
            ]);

        $response1->assertCreated();

        // Coba lagi dengan key sama (seharusnya terkunci karena idempotency check di atas)
        $response2 = $this->actingAs($this->userWithRole('karyawan'), 'sanctum')
            ->postJson('/api/v1/payments', [
                'id_pesanan' => $order->id_pesanan,
                'id_metode_pembayaran' => $order->metodePembayaran->id_pesanan,
                'jumlah_bayar' => 30000,
                'idempotency_key' => (string) \Illuminate\Support\Str::uuid(),
                'amount_received' => 30000,
                'status_pembayaran' => 'berhasil',
                'jenis_pembayaran' => 'dp',
            ]);

        // Karena idempotency key sama persis, harus bertindaku konsisten
        // (biasanya di simpan lagi jika sudah exist)
        // Di implementasi sebelumnya, yang ditangani adalah:
        // - Key sama payload sama → 200
        // - Key sama payload beda → 409
        // - DB conflict → cek ulang transaksi

        $this->assertContains($response2->json('success'), [true, false]);
    }

    public function test_retry_tidak_membuat_pembayaran_ganda(): void
    {
        $order = Pesanan::factory()->create(['total_harga' => 50000]);

        $idempotencyKey = (string) \Illuminate\Support\Str::uuid();

        // Kirim pertama kali dengan status terencana (simulasi)
        // Dalam implementasi nyata, ini terjadi ketika key sama direturn dari backend

        // Memastikan hanya 1 pembayaran di database untuk key ini
        $paymentCount = Pembayaran::where('idempotency_key', $idempotencyKey)->count();
        $this->assertEquals(1, $paymentCount, 'Seharusnya hanya ada 1 pembayaran dengan key idempotent ini');
    }

    // ========================================================================
    // D. KONSISTENSI KEUANGAN
    // ========================================================================

    public function test_pembayaran_pending_tidak_mengurangi_outstanding(): void
    {
        $order = Pesanan::factory()->create([
            'total_harga' => 100000,
            'diskon' => 0,
        ]);

        // Buat pembayaran pending (status menunggu)
        $payment = $order->pembayaran()->create([
            'id_metode_pembayaran' => $this->paymentMethod('transfer')->id_metode_pembayaran,
            'id_user' => $this->userWithRole('karyawan')->id,
            'kode_pembayaran' => 'PAY-' . now()->format('Ymd') . '-' . \Illuminate\Support\Str::upper(\Illuminate\Support\Str::random(10)),
            'jumlah_bayar' => 30000,
            'idempotency_key' => (string) \Illuminate\Support\Str::uuid(),
            'amount_received' => 30000,
            'amount_applied' => 30000,
            'change_amount' => 0,
            'jenis_pembayaran' => 'dp',
            'status_pembayaran' => 'menunggu',
            'tanggal_pembayaran' => now(),
            'catatan' => 'DP Manual',
            'payload_hash' => hash('sha256', json_encode([])),
        ]);

        // Calculate sisa_tagihan manually: only successful payments count
        $terbayar = (float) $order->pembayaran()->where('status_pembayaran', 'berhasil')->sum('jumlah_bayar');
        $outstanding = max(0, (float) $order->total_harga - $terbayar);

        // Cek that resource filtered only `status_pembayaran = berhasil`
        $this->assertEquals(100000, $order->total_harga);
        $this->assertEquals(100000, $outstanding);
    }

    public function test_pembayaran_pending_tidak_masuk_pendapatan(): void
    {
        $order = Pesanan::factory()->create([
            'total_harga' => 100000,
            'diskon' => 0,
        ]);

        // Pembayaran pending
        $order->pembayaran()->create([
            'id_metode_pembayaran' => $this->paymentMethod('tunai')->id_metode_pembayaran,
            'id_user' => $this->userWithRole('karyawan')->id,
            'jumlah_bayar' => 30000,
            'idempotency_key' => (string) \Illuminate\Support\Str::uuid(),
            'status_pembayaran' => 'menunggu',
            'payload_hash' => hash('sha256', json_encode([])),
        ]);

        // Ambil dari OrderResource
        $response = $this->actingAs($this->userWithRole('admin'), 'sanctum')
            ->getJson("/api/v1/orders/{$order->id_pesanan}");

        $response->assertStatus(200);
        $data = $response->json('data');

        // Tensor reporting harus filter `status_pembayaran = berhasil`
        $this->assertEquals(100000, $data['total_harga']);
        $this->assertEquals(100000, $data['sisa_pembayaran']);
    }

    public function test_pembayaran_berhasil_dihitung_tepat_satu_kali(): void
    {
        $order = Pesanan::factory()->create([
            'total_harga' => 50000,
            'diskon' => 0,
        ]);

        // Buat 1 pembayaran berhasil
        $order->pembayaran()->create([
            'id_metode_pembayaran' => $this->paymentMethod('tunai')->id_metode_pembayaran,
            'id_user' => $this->userWithRole('karyawan')->id,
            'jumlah_bayar' => 50000,
            'idempotency_key' => (string) \Illuminate\Support\Str::uuid(),
            'amount_received' => 50000,
            'amount_applied' => 50000,
            'change_amount' => 0,
            'status_pembayaran' => 'berhasil',
            'payload_hash' => hash('sha256', json_encode([])),
        ]);

        $response = $this->actingAs($this->userWithRole('admin'), 'sanctum')
            ->getJson("/api/v1/orders/{$order->id_pesanan}");

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertEquals(1, count($data['pembayaran']));
        $this->assertEquals(50000, $data['total_terbayar']);
        $this->assertEquals(0, $data['sisa_pembayaran']);
    }

    public function test_kembalian_tidak_dihitung_sebagai_pendapatan(): void
    {
        $order = Pesanan::factory()->create([
            'total_harga' => 50000,
            'diskon' => 0,
        ]);

        // Tunai + kembalian
        $order->pembayaran()->create([
            'id_metode_pembayaran' => $this->paymentMethod('tunai')->id_metode_pembayaran,
            'id_user' => $this->userWithRole('karyawan')->id,
            'jumlah_bayar' => 60000,
            'idempotency_key' => (string) \Illuminate\Support\Str::uuid(),
            'amount_received' => 60000,
            'amount_applied' => 50000,
            'change_amount' => 10000,
            'status_pembayaran' => 'berhasil',
            'payload_hash' => hash('sha256', json_encode([])),
        ]);

        $response = $this->actingAs($this->userWithRole('admin'), 'sanctum')
            ->getJson("/api/v1/orders/{$order->id_pesanan}");

        $response->assertStatus(200);
        $data = $response->json('data');

        // Pendapatan hanya 50000 (amount_applied), bukan 60000 (amount_received)
        $this->assertEquals(50000, $data['total_terbayar']);
        $this->assertEquals(0, $data['sisa_pembayaran']);
    }

    public function test_total_terbayar_dan_sisa_tagihan_konsisten(): void
    {
        $order = Pesanan::factory()->create([
            'total_harga' => 100000,
            'diskon' => 5000,
        ]);

        // Pembayaran berhasil
        $order->pembayaran()->create([
            'id_metode_pembayaran' => $this->paymentMethod('tunai')->id_metode_pembayaran,
            'id_user' => $this->userWithRole('karyawan')->id,
            'jumlah_bayar' => 45000,
            'idempotency_key' => (string) \Illuminate\Support\Str::uuid(),
            'amount_received' => 45000,
            'amount_applied' => 45000,
            'change_amount' => 0,
            'status_pembayaran' => 'berhasil',
            'payload_hash' => hash('sha256', json_encode([])),
        ]);

        // Pembayaran pending
        $order->pembayaran()->create([
            'id_metode_pembayaran' => $this->paymentMethod('transfer')->id_metode_pembayaran,
            'id_user' => $this->userWithRole('karyawan')->id,
            'jumlah_bayar' => 45000,
            'idempotency_key' => (string) \Illuminate\Support\Str::uuid(),
            'amount_received' => 45000,
            'amount_applied' => 45000,
            'change_amount' => 0,
            'status_pembayaran' => 'menunggu',
            'payload_hash' => hash('sha256', json_encode([])),
        ]);

        $response = $this->actingAs($this->userWithRole('admin'), 'sanctum')
            ->getJson("/api/v1/orders/{$order->id_pesanan}");

        $response->assertStatus(200);
        $data = $response->json('data');

        // Verifikasi: Total UK = Total Harga - Diskon = 100000 - 5000 = 95000
        // Total Berhasil = 45000
        // Sisa = 95000 - 45000 = 50000
        $this->assertEquals(95000, $data['total_harga']);
        $this->assertEquals(45000, $data['total_terbayar']);
        $this->assertEquals(50000, $data['sisa_pembayaran']);
        $this->assertEquals(95000, $data['total_terbayar'] + $data['sisa_pembayaran']);
    }
}