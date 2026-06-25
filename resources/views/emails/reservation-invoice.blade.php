<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Reservasi — CaffeSeek</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #F4F4F0;
            color: #1F1F1F;
            padding: 32px 16px;
        }
        .wrapper {
            max-width: 600px;
            margin: 0 auto;
        }
        /* HEADER */
        .header {
            background-color: #1A1A1A;
            border-radius: 20px 20px 0 0;
            padding: 32px 40px;
            text-align: center;
        }
        .logo-text {
            font-size: 26px;
            font-weight: 900;
            color: #BDEE63;
            letter-spacing: -0.5px;
        }
        .logo-dot {
            color: #ffffff;
        }
        .header-subtitle {
            font-size: 13px;
            color: #888888;
            margin-top: 6px;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }
        /* INVOICE BADGE */
        .invoice-badge {
            background-color: #BDEE63;
            border-radius: 0;
            padding: 16px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .invoice-badge .label {
            font-size: 12px;
            font-weight: 700;
            color: #4A5500;
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }
        .invoice-badge .invoice-number {
            font-size: 18px;
            font-weight: 900;
            color: #1A1A1A;
            font-family: 'Courier New', monospace;
        }
        .invoice-badge .status-badge {
            background-color: #1A1A1A;
            color: #BDEE63;
            font-size: 11px;
            font-weight: 700;
            padding: 5px 12px;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }
        /* BODY */
        .body {
            background-color: #ffffff;
            padding: 40px;
        }
        .greeting {
            font-size: 22px;
            font-weight: 800;
            color: #1A1A1A;
            margin-bottom: 8px;
        }
        .greeting-sub {
            font-size: 14px;
            color: #6B6B6B;
            line-height: 1.6;
            margin-bottom: 32px;
        }
        /* INFO GRID */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 32px;
        }
        .info-card {
            background-color: #F7F8F2;
            border-radius: 12px;
            padding: 16px;
        }
        .info-card .info-label {
            font-size: 11px;
            font-weight: 700;
            color: #9AA05B;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin-bottom: 4px;
        }
        .info-card .info-value {
            font-size: 14px;
            font-weight: 600;
            color: #1A1A1A;
        }
        /* SECTION TITLE */
        .section-title {
            font-size: 12px;
            font-weight: 700;
            color: #9AA05B;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            margin-bottom: 12px;
        }
        /* ITEMS TABLE */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 32px;
        }
        .items-table thead tr {
            background-color: #1A1A1A;
        }
        .items-table thead th {
            padding: 12px 16px;
            font-size: 11px;
            font-weight: 700;
            color: #BDEE63;
            text-align: left;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }
        .items-table thead th:last-child {
            text-align: right;
        }
        .items-table thead th:first-child {
            border-radius: 10px 0 0 10px;
        }
        .items-table thead th:last-child {
            border-radius: 0 10px 10px 0;
        }
        .items-table tbody tr {
            border-bottom: 1px solid #F0F0F0;
        }
        .items-table tbody tr:last-child {
            border-bottom: none;
        }
        .items-table tbody td {
            padding: 14px 16px;
            font-size: 14px;
            color: #1A1A1A;
            vertical-align: middle;
        }
        .items-table tbody td:last-child {
            text-align: right;
            font-weight: 600;
        }
        .item-type-badge {
            display: inline-block;
            font-size: 10px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 10px;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-left: 6px;
        }
        .badge-menu { background: #FFF3E0; color: #E65100; }
        .badge-table { background: #E3F2FD; color: #0D47A1; }
        .badge-meeting { background: #EDE7F6; color: #4A148C; }
        .badge-free { font-size: 11px; color: #9AA05B; font-weight: 500; }
        /* TOTAL SECTION */
        .total-section {
            background: linear-gradient(135deg, #1A1A1A 0%, #2D2D2D 100%);
            border-radius: 16px;
            padding: 24px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 32px;
        }
        .total-label {
            font-size: 13px;
            color: #BDEE63;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }
        .total-amount {
            font-size: 26px;
            font-weight: 900;
            color: #BDEE63;
        }
        /* NOTE */
        .note-box {
            background-color: #F7F8F2;
            border-left: 4px solid #BDEE63;
            border-radius: 0 12px 12px 0;
            padding: 16px 20px;
            margin-bottom: 32px;
        }
        .note-box p {
            font-size: 13px;
            color: #4A4A4A;
            line-height: 1.6;
        }
        .note-box strong {
            color: #1A1A1A;
        }
        /* FOOTER */
        .footer {
            background-color: #1A1A1A;
            border-radius: 0 0 20px 20px;
            padding: 28px 40px;
            text-align: center;
        }
        .footer-logo {
            font-size: 18px;
            font-weight: 900;
            color: #BDEE63;
            margin-bottom: 8px;
        }
        .footer-text {
            font-size: 12px;
            color: #666666;
            line-height: 1.7;
        }
        .footer-text a {
            color: #BDEE63;
            text-decoration: none;
        }
        /* RESPONSIVE */
        @media (max-width: 480px) {
            .body { padding: 24px 20px; }
            .header { padding: 24px 20px; }
            .invoice-badge { padding: 14px 20px; flex-direction: column; gap: 10px; text-align: center; }
            .info-grid { grid-template-columns: 1fr; }
            .total-section { flex-direction: column; gap: 8px; text-align: center; }
            .total-amount { font-size: 22px; }
        }
    </style>
</head>
<body>
    <div class="wrapper">

        {{-- HEADER --}}
        <div class="header">
            <div class="logo-text">Caffe<span class="logo-dot">S</span>eek</div>
            <div class="header-subtitle">Konfirmasi & Invoice Reservasi</div>
        </div>

        {{-- INVOICE BADGE --}}
        <div class="invoice-badge">
            <div>
                <div class="label">Nomor Invoice</div>
                <div class="invoice-number">INV-{{ str_pad($reservation->id, 5, '0', STR_PAD_LEFT) }}</div>
            </div>
            <div class="status-badge">✓ Lunas</div>
        </div>

        {{-- BODY --}}
        <div class="body">
            <div class="greeting">Halo, {{ $reservation->customer_name }}! 👋</div>
            <div class="greeting-sub">
                Pembayaran Anda telah <strong>dikonfirmasi</strong> oleh tim CaffeSeek. 
                Berikut adalah detail lengkap reservasi Anda. Mohon simpan email ini sebagai bukti pemesanan.
            </div>

            {{-- INFO GRID --}}
            <div class="info-grid">
                <div class="info-card">
                    <div class="info-label">Nama Pemesan</div>
                    <div class="info-value">{{ $reservation->customer_name }}</div>
                </div>
                <div class="info-card">
                    <div class="info-label">No. WhatsApp</div>
                    <div class="info-value">{{ $reservation->customer_whatsapp }}</div>
                </div>
                <div class="info-card">
                    <div class="info-label">Caffe & Resto</div>
                    <div class="info-value">{{ $reservation->cafe->name }}</div>
                </div>
                <div class="info-card">
                    <div class="info-label">Tanggal Reservasi</div>
                    <div class="info-value">
                        {{ \Carbon\Carbon::parse($reservation->reservation_date)->translatedFormat('d F Y') }}
                    </div>
                </div>
            </div>

            {{-- ITEMS --}}
            <div class="section-title">Detail Pesanan</div>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th style="text-align:center;">Qty</th>
                        <th>Harga Satuan</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($reservation->items as $item)
                    <tr>
                        <td>
                            {{ $item->name }}
                            @if($item->item_type === 'menu')
                                <span class="item-type-badge badge-menu">Menu</span>
                            @elseif($item->item_type === 'table')
                                <span class="item-type-badge badge-table">Meja</span>
                            @elseif($item->item_type === 'meeting_room')
                                <span class="item-type-badge badge-meeting">Meeting</span>
                            @endif
                        </td>
                        <td style="text-align:center;">{{ $item->quantity }}</td>
                        <td>
                            @if($item->price > 0)
                                {{ 'Rp ' . number_format($item->price, 0, ',', '.') }}
                            @else
                                <span class="badge-free">Termasuk</span>
                            @endif
                        </td>
                        <td>
                            @if($item->price > 0)
                                {{ 'Rp ' . number_format($item->price * $item->quantity, 0, ',', '.') }}
                            @else
                                <span class="badge-free">—</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            {{-- TOTAL --}}
            <div class="total-section">
                <div class="total-label">Total Pembayaran</div>
                <div class="total-amount">Rp {{ number_format($reservation->total_price, 0, ',', '.') }}</div>
            </div>

            {{-- NOTE --}}
            <div class="note-box">
                <p>
                    📍 <strong>Lokasi:</strong> {{ $reservation->cafe->location ?? 'Lihat di Google Maps' }}<br>
                    📞 <strong>Kontak Kafe:</strong> {{ $reservation->cafe->whatsapp }}<br><br>
                    Harap tunjukkan email ini atau nomor invoice <strong>INV-{{ str_pad($reservation->id, 5, '0', STR_PAD_LEFT) }}</strong> 
                    kepada kasir saat tiba. Reservasi berlaku sesuai tanggal yang tertera.
                </p>
            </div>
        </div>

        {{-- FOOTER --}}
        <div class="footer">
            <div class="footer-logo">CaffeSeek</div>
            <div class="footer-text">
                Platform reservasi kafe terpercaya di Indonesia.<br>
                Email ini dikirim otomatis, harap tidak membalas langsung.<br>
                Pertanyaan? Hubungi kami melalui WhatsApp kafe terkait.
            </div>
        </div>

    </div>
</body>
</html>
