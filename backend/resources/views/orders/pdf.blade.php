<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Pesanan {{ $order->kode_pesanan }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            color: #1f2937;
            font-size: 11px;
            margin: 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 7px 8px;
            border: 1px solid #d1d5db;
            text-align: left;
        }
        th {
            background: #f3f4f6;
            font-weight: bold;
        }
        .text-right { text-align: right; }
        .muted { color: #6b7280; }
    </style>
</head>
<body>
    @php
        $statusLabels = [
            'menunggu' => 'Menunggu giliran',
            'dicuci' => 'Di cuci',
            'dikeringkan' => 'Di keringkan',
            'dilipat' => 'Di lipat',
            'disetrika' => 'Di setrika',
            'diproses' => 'Diproses',
            'siap_diambil' => 'Siap diambil',
            'dalam_pengantaran' => 'Dalam pengantaran',
            'selesai' => 'Selesai',
            'dibatalkan' => 'Dibatalkan',
        ];
    @endphp

    <table style="border: none; margin-bottom: 14px;">
        <tr>
            <td style="border: none; padding: 0;">
                <div style="font-size: 20px; font-weight: bold; color: #0f766e;">PEMUDA LAUNDRY</div>
                <div class="muted">Kartu pesanan &mdash; cetak ini untuk pelanggan</div>
            </td>
            <td style="border: none; padding: 0; text-align: right;">
                <div class="muted">No. Pesanan</div>
                <div style="font-size: 15px; font-weight: bold;">{{ $order->kode_pesanan }}</div>
            </td>
        </tr>
    </table>

    <table style="margin-bottom: 14px;">
        <tr>
            <th style="width: 25%;">Nama pelanggan</th>
            <td>{{ $order->nama_pelanggan }}</td>
            <th style="width: 20%;">No. pesanan</th>
            <td>{{ $order->kode_pesanan }}</td>
        </tr>
        <tr>
            <th>No. HP</th>
            <td>{{ $order->no_hp }}</td>
            <th>Status</th>
            <td>{{ $statusLabels[$order->status_pesanan] ?? $order->status_pesanan }}</td>
        </tr>
        <tr>
            <th>Tanggal masuk</th>
            <td>{{ $order->tanggal_masuk?->format('d/m/Y H:i') ?? '-' }}</td>
            <th>Estimasi selesai</th>
            <td>{{ $order->estimasi_selesai?->format('d/m/Y H:i') ?? '-' }}</td>
        </tr>
        @if($order->alamat)
            <tr>
                <th>Alamat</th>
                <td colspan="3">{{ $order->alamat }}</td>
            </tr>
        @endif
    </table>

    <div style="font-weight: bold; margin-bottom: 6px;">Layanan</div>
    <table style="margin-bottom: 10px;">
        <thead>
            <tr>
                <th style="width: 8%;">No</th>
                <th>Nama layanan</th>
                <th style="width: 14%;" class="text-right">Jumlah</th>
                <th style="width: 18%;" class="text-right">Harga</th>
                <th style="width: 20%;" class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->detailPesanan as $index => $detail)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $detail->nama_layanan }}</td>
                    <td class="text-right">{{ $detail->jumlah }} {{ $detail->satuan }}</td>
                    <td class="text-right">Rp {{ number_format($detail->harga_satuan, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($detail->subtotal, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="4" class="text-right">Subtotal</th>
                <td class="text-right">Rp {{ number_format($order->subtotal, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <th colspan="4" class="text-right">Diskon</th>
                <td class="text-right">Rp {{ number_format($order->diskon, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <th colspan="4" class="text-right">Total</th>
                <td class="text-right"><strong>Rp {{ number_format($order->total_harga, 0, ',', '.') }}</strong></td>
            </tr>
        </tfoot>
    </table>

    <table style="border: none; margin-top: 18px;">
        <tr>
            <td style="border: none; padding: 0; text-align: center; width: 210px;">
                <img src="{{ $qrCode }}" alt="QR pesanan" style="width: 150px; height: 150px;">
                <div class="muted" style="margin-top: 6px; font-size: 9px;">
                    Scan QR di dashboard karyawan<br>untuk melihat rincian &amp; mengubah status
                </div>
            </td>
            <td style="border: none; padding: 0; vertical-align: bottom;">
                <div class="muted">Kode pesanan (untuk input manual):</div>
                <div style="font-size: 14px; font-weight: bold; letter-spacing: 1px;">{{ $order->kode_pesanan }}</div>
                @if($order->catatan)
                    <div class="muted" style="margin-top: 10px;">Catatan: {{ $order->catatan }}</div>
                @endif
            </td>
        </tr>
    </table>
</body>
</html>
