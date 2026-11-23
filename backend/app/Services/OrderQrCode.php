<?php

namespace App\Services;

use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;

/**
 * Membuat PNG QR pesanan.
 *
 * Paket QR yang terpasang hanya menyediakan backend PNG via imagick,
 * sehingga matrix QR-nya digambar langsung dengan GD yang tersedia.
 */
class OrderQrCode
{
    public function png(string $content, int $size = 190, int $margin = 2): string
    {
        $matrix = Encoder::encode(
            $content,
            ErrorCorrectionLevel::M(),
            'UTF-8',
            null,
            false
        )->getMatrix();

        $modules = $matrix->getWidth();
        $total = $modules + ($margin * 2);
        $scale = max(1, intdiv($size, $total));
        $pixelSize = $total * $scale;

        $image = imagecreatetruecolor($pixelSize, $pixelSize);
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        imagefill($image, 0, 0, $white);

        for ($y = 0; $y < $modules; $y++) {
            for ($x = 0; $x < $modules; $x++) {
                if ($matrix->get($x, $y) !== 1) {
                    continue;
                }

                $left = ($x + $margin) * $scale;
                $top = ($y + $margin) * $scale;

                imagefilledrectangle(
                    $image,
                    $left,
                    $top,
                    $left + $scale - 1,
                    $top + $scale - 1,
                    $black
                );
            }
        }

        ob_start();
        imagepng($image);

        return (string) ob_get_clean();
    }
}
