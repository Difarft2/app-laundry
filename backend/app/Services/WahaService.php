<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\Pesanan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WahaService
{
    protected $config;

    public function __construct()
    {
        $this->config = config('services.waha');
    }

    protected function getAppSetting(): AppSetting
    {
        return AppSetting::first() ?? new AppSetting(AppSetting::DEFAULTS);
    }

    public function sendText(string $chatId, string $text): bool
    {
        if (empty($this->config['base_url']) || empty($this->config['api_key'])) {
            return false;
        }

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'X-Api-Key' => $this->config['api_key'],
        ])->post($this->config['base_url'].'/api/sendText', [
            'session' => $this->config['session'] ?? 'default',
            'chatId' => $chatId,
            'text' => $text,
        ]);

        if (! $response->successful()) {
            Log::error('WAHA gagal mengirim pesan.', [
                'status' => $response->status(),
                'body' => $response->body(),
                'chat_id' => $chatId,
                'session' => $this->config['session'] ?? null,
            ]);
        }

        return $response->successful();
    }

    public function sendOrderInvoice(Pesanan $order): void
    {
        $config = config('services.waha');

        if (empty($config['base_url']) || empty($config['api_key']) || empty($config['landing_url'])) {
            Log::warning('WA Invoice tidak dikirim: konfigurasi Waha belum lengkap.', [
                'kode_pesanan' => $order->kode_pesanan,
            ]);

            return;
        }

        $trackUrl = rtrim($config['landing_url'], '/').'/track/'.rawurlencode($order->kode_pesanan);

        $setting = $this->getAppSetting();

        // Format tanggal
        $tanggalMasuk = $order->tanggal_masuk->format('d/m/Y - H:i');
        $estimasiSelesai = $order->estimasi_selesai->format('d/m/Y - H:i');

        // Bangun item detail
        $items = $order->detailPesanan
            ->map(function ($detail) {
                $qty = (int) $detail->jumlah;
                $harga = (int) $detail->harga_satuan;
                $subtotalItem = $qty * $harga;
                return "- {$detail->nama_layanan}\n{$qty} {$detail->satuan} x ".number_format($harga, 0, ',', '.')." = Rp ".number_format($subtotalItem, 0, ',', '.');
            })
            ->join("\n\n");

        // Status pembayaran
        $statusPembayaran = $order->pembayaran->where('status_pembayaran', 'lunas')->isNotEmpty() ? '*LUNAS*' : '*BELUM LUNAS*';

        $text = "*NOTA ELEKTRONIK*\n\n"
            .$setting->nama_app."\n"
            
            ."=======================\n"
            ."No Nota :\n{$order->kode_pesanan}\n\n"
            ."Pelanggan :\n{$order->nama_pelanggan}\n"
            ."No HP : {$order->no_hp}\n\n"
            ."Tanggal Masuk    :\n{$tanggalMasuk}\n\n"
            ."Estimasi Selesai :\n{$estimasiSelesai}\n\n"
            ."=======================\n"
            ."{$items}\n\n"
            ."=======================\n"
            ."Status   : {$statusPembayaran}\n\n"
            ."=======================\n"
            ."subTotal  =  Rp ".number_format((float) $order->subtotal, 0, ',', '.')."\n"
            ."Diskon     =  Rp ".number_format((float) $order->diskon, 0, ',', '.')."\n"
            ."Total        =  *Rp ".number_format((float) $order->total_harga, 0, ',', '.')."*\n\n"
            ."- Pengaduan komplain dilayani 1x24 jam\n"
            ."- Kerusakan luntur, berkerut, dan susut akibat proses pencucian bukan tanggung jawab {$setting->nama_app}\n"
            ."- {$setting->nama_app} tidak bertanggung jawab apabila terjadi Force Majeur (banjir, kebakaran, gempa bumi, huru-hara)\n\n"
            ."{$trackUrl}\n\n"
            ."Terima Kasih";

        $this->sendText($this->chatId($order->no_hp), $text);
    }

    private function chatId(string $phone): string
    {
        $phone = preg_replace('/\D+/', '', $phone);

        if (str_starts_with($phone, '0')) {
            $phone = '62'.substr($phone, 1);
        }

        return $phone.'@c.us';
    }
}
