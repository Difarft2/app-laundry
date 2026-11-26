<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Models\Pesanan;

/**
 * Membuat gambar struk pesanan (PNG) berisi identitas, layanan, dan QR.
 */
class OrderReceiptImage
{
    private const WIDTH = 440;

    private const PADDING = 34;

    public function __construct(private readonly OrderQrCode $qrCode)
    {
    }

    public function generate(Pesanan $order): string
    {
        $order->loadMissing('detailPesanan');
        $setting = AppSetting::first() ?? new AppSetting(AppSetting::DEFAULTS);
        $items = $order->detailPesanan
            ->map(fn ($detail) => $detail->nama_layanan.' · '.(float) $detail->jumlah.' '.strtolower((string) $detail->satuan))
            ->filter()
            ->values();

        $height = 620 + ($items->count() * 28);
        $image = imagecreatetruecolor(self::WIDTH, $height);

        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 17, 24, 39);
        $muted = imagecolorallocate($image, 107, 114, 128);

        imagefill($image, 0, 0, $white);

        $this->centeredText($image, strtoupper((string) $setting->nama_app), 22, 52, $black, true);
        $this->dashedLine($image, 82, $muted);

        $rows = [
            ['NAMA', $order->nama_pelanggan],
            ['NO. PESANAN', $order->kode_pesanan],
            ['TANGGAL MASUK', $order->tanggal_masuk?->format('d/m/Y H:i') ?? '-'],
            ['ESTIMASI SELESAI', $order->estimasi_selesai?->format('d/m/Y H:i') ?? '-'],
        ];

        $y = 112;

        foreach ($rows as [$label, $value]) {
            $this->text($image, $label, 12, self::PADDING, $y, $muted);
            $this->text($image, (string) $value, 17, self::PADDING, $y + 22, $black, true);
            $y += 62;
        }

        $this->dashedLine($image, $y, $muted);
        $y += 30;
        $this->text($image, 'LAYANAN', 12, self::PADDING, $y, $muted);
        $y += 28;

        foreach ($items as $item) {
            $this->text($image, (string) $item, 16, self::PADDING, $y, $black, true);
            $y += 28;
        }

        $y += 8;
        $this->dashedLine($image, $y, $muted);

        $qrSize = 190;
        $qr = imagecreatefromstring($this->qrCode->png($order->kode_pesanan, $qrSize));

        if ($qr !== false) {
            imagecopy(
                $image,
                $qr,
                intdiv(self::WIDTH - $qrSize, 2),
                $y + 24,
                0,
                0,
                imagesx($qr),
                imagesy($qr)
            );
        }

        ob_start();
        imagepng($image);

        return (string) ob_get_clean();
    }

    private function dashedLine($image, int $y, int $color): void
    {
        for ($x = self::PADDING; $x < self::WIDTH - self::PADDING; $x += 12) {
            imageline(
                $image,
                $x,
                $y,
                min($x + 6, self::WIDTH - self::PADDING),
                $y,
                $color
            );
        }
    }

    private function text(
        $image,
        string $value,
        int $size,
        int $x,
        int $y,
        int $color,
        bool $bold = false
    ): void {
        $font = $this->font($bold);

        if ($font) {
            imagettftext($image, $size, 0, $x, $y, $color, $font, $value);
            return;
        }

        imagestring($image, $bold ? 5 : 3, $x, $y - $size, $value, $color);
    }

    private function centeredText(
        $image,
        string $value,
        int $size,
        int $y,
        int $color,
        bool $bold = false
    ): void {
        $font = $this->font($bold);

        if (!$font) {
            $this->text($image, $value, $size, self::PADDING, $y, $color, $bold);
            return;
        }

        $box = imagettfbbox($size, 0, $font, $value);
        $width = $box ? max($box[2], $box[4]) - min($box[0], $box[6]) : 0;

        $this->text(
            $image,
            $value,
            $size,
            intdiv(self::WIDTH - $width, 2),
            $y,
            $color,
            $bold
        );
    }

    private function font(bool $bold): ?string
    {
        $candidates = $bold
            ? [
                '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
                '/usr/share/fonts/TTF/DejaVuSans-Bold.ttf',
            ]
            : [
                '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
                '/usr/share/fonts/TTF/DejaVuSans.ttf',
            ];

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }
}