<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nota {{ $order->order_number }} — {{ $business['name'] }}</title>

    <!-- Aset CSS 100% Offline-First (Tanpa CDN / Google Fonts) -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">

    <style>
        /* =================================================================
         * STYLING PRATINJAU LAYAR NOTA (SCREEN MODE)
         * =============================================================== */
        body {
            margin: 0;
            padding: 0;
            background-color: #e2e8f0;
            color: #0f172a;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Toolbar Kontrol Cetak di Layar */
        .print-toolbar {
            background-color: #ffffff;
            border-bottom: 1px solid #cbd5e1;
            padding: 12px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 16px;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .print-toolbar-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .format-toggle-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            font-size: 13px;
            font-weight: 600;
            border-radius: 6px;
            text-decoration: none;
            border: 1px solid #cbd5e1;
            background-color: #f8fafc;
            color: #475569;
            transition: all 0.15s ease;
        }

        .format-toggle-btn:hover {
            background-color: #e2e8f0;
            color: #0f172a;
        }

        .format-toggle-btn.active {
            background-color: #2563eb;
            color: #ffffff;
            border-color: #2563eb;
            box-shadow: 0 1px 3px rgba(37, 99, 235, 0.3);
        }

        /* Lembar Kertas Pratinjau di Layar */
        .print-viewport {
            flex: 1;
            padding: 30px 16px;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            overflow-y: auto;
        }

        .paper-sheet {
            background-color: #ffffff;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            box-sizing: border-box;
            color: #000000;
        }

        .paper-sheet-a4 {
            width: 210mm;
            min-height: 297mm;
            padding: 20mm 18mm;
            border-radius: 4px;
        }

        .paper-sheet-thermal {
            width: 80mm;
            max-width: 100%;
            padding: 8mm 6mm;
            border-radius: 4px;
            font-family: "Courier New", Courier, monospace, system-ui;
        }

        /* =================================================================
         * STYLING FORMAT THERMAL (STRUK KASIR 58mm / 80mm)
         * =============================================================== */
        .thermal-receipt {
            font-size: 12px;
            line-height: 1.4;
            color: #000000;
        }

        .thermal-header {
            text-align: center;
            margin-bottom: 6px;
        }

        .thermal-business-name {
            font-size: 16px;
            font-weight: 800;
            margin: 0 0 4px 0;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .thermal-business-info {
            font-size: 11px;
            color: #222222;
            margin: 1px 0;
        }

        .thermal-divider {
            text-align: center;
            font-weight: bold;
            letter-spacing: -1px;
            margin: 6px 0;
            overflow: hidden;
            white-space: nowrap;
        }

        .thermal-divider-dotted {
            text-align: center;
            letter-spacing: -1px;
            margin: 4px 0;
            overflow: hidden;
            white-space: nowrap;
        }

        .thermal-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin: 2px 0;
            font-size: 12px;
        }

        .thermal-meta {
            margin: 6px 0;
        }

        .thermal-cancelled-box {
            border: 2px solid #000;
            padding: 6px;
            text-align: center;
            font-weight: bold;
            margin: 6px 0;
            font-size: 13px;
        }

        .thermal-items {
            margin: 6px 0;
        }

        .thermal-table-head {
            font-weight: bold;
            font-size: 11px;
        }

        .thermal-item-block {
            margin: 5px 0;
        }

        .thermal-item-name {
            font-weight: bold;
            font-size: 12px;
        }

        .thermal-item-calc {
            font-size: 11px;
            color: #111111;
        }

        .thermal-item-note {
            font-size: 10px;
            font-style: italic;
            color: #333333;
            margin-top: 1px;
        }

        .thermal-financials {
            margin: 6px 0;
        }

        .thermal-total-row {
            font-size: 14px;
            font-weight: bold;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
            padding: 4px 0;
            margin: 4px 0;
        }

        .thermal-balance-row {
            font-size: 13px;
            font-weight: bold;
            padding: 2px 0;
        }

        .thermal-stamp-box {
            text-align: center;
            margin: 10px 0;
        }

        .thermal-stamp {
            display: inline-block;
            border: 2px solid #000000;
            padding: 4px 14px;
            font-weight: 800;
            font-size: 14px;
            letter-spacing: 0.05em;
        }

        .thermal-order-notes {
            font-size: 11px;
            margin: 6px 0;
            background-color: #f8fafc;
            padding: 6px;
            border: 1px dashed #cbd5e1;
        }

        .thermal-footer {
            text-align: center;
            margin-top: 10px;
            font-size: 11px;
        }

        .thermal-footer-note {
            margin-bottom: 8px;
            line-height: 1.3;
        }

        .thermal-thank-you {
            font-weight: bold;
            margin-bottom: 4px;
        }

        .thermal-print-time {
            font-size: 10px;
            color: #444444;
        }

        /* =================================================================
         * STYLING FORMAT FAKTUR A4 FORMAL
         * =============================================================== */
        .a4-receipt {
            font-size: 13px;
            line-height: 1.5;
            color: #000000;
        }

        .a4-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 16px;
            margin-bottom: 20px;
        }

        .a4-business-col {
            flex: 1;
            padding-right: 20px;
        }

        .a4-business-name {
            font-size: 24px;
            font-weight: 800;
            color: #0f172a;
            margin: 0 0 4px 0;
            letter-spacing: -0.02em;
        }

        .a4-business-desc {
            font-size: 12px;
            color: #475569;
            margin: 0 0 8px 0;
        }

        .a4-business-meta {
            font-size: 12px;
            color: #334155;
            line-height: 1.4;
        }

        .a4-invoice-col {
            text-align: right;
        }

        .a4-invoice-title {
            font-size: 20px;
            font-weight: 800;
            letter-spacing: 0.05em;
            color: #2563eb;
            margin-bottom: 4px;
        }

        .a4-order-number {
            font-size: 18px;
            font-weight: 700;
            color: #0f172a;
            font-family: "Courier New", Courier, monospace;
            margin-bottom: 8px;
        }

        .a4-status-badges {
            display: flex;
            justify-content: flex-end;
            gap: 6px;
            margin-bottom: 8px;
        }

        .a4-badge {
            display: inline-block;
            font-size: 11px;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 4px;
            text-transform: uppercase;
        }

        .a4-badge-laundry {
            background-color: #dbeafe;
            color: #1e40af;
            border: 1px solid #bfdbfe;
        }

        .a4-badge-paid {
            background-color: #dcfce7;
            color: #15803d;
            border: 1px solid #bbf7d0;
        }

        .a4-badge-unpaid {
            background-color: #fee2e2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }

        .a4-meta-dates {
            font-size: 12px;
            color: #334155;
            line-height: 1.4;
        }

        .a4-cancelled-alert {
            background-color: #fee2e2;
            border: 1px solid #ef4444;
            color: #991b1b;
            padding: 10px 14px;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .a4-cancelled-title {
            font-weight: 700;
            font-size: 13px;
        }

        .a4-cancelled-desc {
            font-size: 12px;
            margin-top: 4px;
        }

        .a4-meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            margin-bottom: 20px;
        }

        .a4-meta-card {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 12px 14px;
            background-color: #f8fafc;
        }

        .a4-meta-card-title {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            color: #475569;
            letter-spacing: 0.05em;
            margin-bottom: 8px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 4px;
        }

        .a4-meta-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        .a4-meta-table td {
            padding: 2px 0;
            vertical-align: top;
        }

        .a4-table-container {
            margin-bottom: 20px;
        }

        .a4-items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }

        .a4-items-table th {
            background-color: #f1f5f9;
            color: #0f172a;
            font-weight: 700;
            border: 1px solid #cbd5e1;
            padding: 8px 10px;
        }

        .a4-items-table td {
            border: 1px solid #cbd5e1;
            padding: 8px 10px;
            vertical-align: top;
        }

        .a4-item-name {
            font-weight: 600;
            color: #0f172a;
        }

        .a4-item-note {
            font-size: 11px;
            color: #64748b;
            font-style: italic;
            margin-top: 2px;
        }

        .a4-bottom-grid {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .a4-section-box {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px 12px;
            background-color: #f8fafc;
        }

        .a4-section-title {
            font-size: 12px;
            font-weight: 700;
            color: #334155;
            margin-bottom: 8px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 4px;
        }

        .a4-payments-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }

        .a4-payments-table th {
            background-color: #e2e8f0;
            padding: 4px 6px;
            text-align: left;
            border: 1px solid #cbd5e1;
        }

        .a4-payments-table td {
            padding: 4px 6px;
            border: 1px solid #cbd5e1;
        }

        .a4-cash-tendered-info {
            font-size: 11px;
            color: #475569;
            margin-top: 6px;
            background-color: #ffffff;
            padding: 4px 8px;
            border-radius: 4px;
            border: 1px solid #e2e8f0;
        }

        .a4-no-payment-notice {
            font-size: 11px;
            color: #64748b;
            font-style: italic;
            padding: 6px 0;
        }

        .a4-terms-box {
            margin-top: 14px;
            padding: 10px 12px;
            border: 1px dashed #cbd5e1;
            border-radius: 6px;
            background-color: #fafafa;
        }

        .a4-terms-title {
            font-size: 11px;
            font-weight: 700;
            color: #334155;
            margin-bottom: 4px;
        }

        .a4-terms-content {
            font-size: 10px;
            color: #475569;
            line-height: 1.4;
        }

        .a4-summary-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .a4-summary-table td {
            padding: 6px 8px;
            border-bottom: 1px solid #e2e8f0;
        }

        .a4-summary-total-row td {
            font-weight: 800;
            font-size: 15px;
            background-color: #f1f5f9;
            border-top: 2px solid #0f172a;
            border-bottom: 2px solid #0f172a;
        }

        .a4-summary-balance-row td {
            font-weight: 800;
            font-size: 14px;
            background-color: #fafafa;
            border-bottom: 2px solid #cbd5e1;
        }

        .a4-stamp-wrapper {
            margin-top: 16px;
            display: flex;
            justify-content: flex-end;
        }

        .a4-stamp {
            display: inline-block;
            border-width: 3px;
            border-style: solid;
            border-radius: 8px;
            padding: 8px 24px;
            text-align: center;
            font-weight: 900;
            font-size: 16px;
            letter-spacing: 0.1em;
            transform: rotate(-4deg);
        }

        .a4-stamp-paid {
            border-color: #16a34a;
            color: #16a34a;
            background-color: rgba(22, 163, 74, 0.05);
        }

        .a4-stamp-unpaid {
            border-color: #dc2626;
            color: #dc2626;
            background-color: rgba(220, 38, 38, 0.05);
        }

        .a4-signatures-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-top: 40px;
            page-break-inside: avoid;
        }

        .a4-sign-box {
            text-align: center;
            font-size: 12px;
        }

        .a4-sign-line {
            height: 60px;
            border-bottom: 1px solid #0f172a;
            margin-bottom: 6px;
        }

        /* =================================================================
         * CSS @MEDIA PRINT KHUSUS HALAMAN NOTA (ZERO WEBSITE ARTIFACTS)
         * =============================================================== */
        @media print {
            body {
                background: #ffffff !important;
                color: #000000 !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            .print-toolbar,
            .no-print {
                display: none !important;
            }

            .print-viewport {
                padding: 0 !important;
                margin: 0 !important;
                display: block !important;
                overflow: visible !important;
            }

            .paper-sheet {
                box-shadow: none !important;
                border-radius: 0 !important;
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
            }

            .paper-sheet-a4 {
                width: 100% !important;
                min-height: auto !important;
                padding: 0 !important;
            }

            .paper-sheet-thermal {
                width: 100% !important;
                max-width: 80mm !important;
                padding: 0 !important;
                margin: 0 auto !important;
            }

            /* Proteksi Pemisahan Halaman */
            .a4-header,
            .a4-meta-grid,
            .a4-table-container,
            .a4-bottom-grid,
            .a4-signatures-grid,
            .thermal-receipt {
                page-break-inside: avoid;
            }

            /* Pastikan badge dan tabel tetap memiliki border tegas saat dicetak */
            .a4-badge-laundry,
            .a4-badge-paid,
            .a4-badge-unpaid,
            .a4-items-table th,
            .a4-items-table td,
            .a4-meta-card,
            .a4-section-box,
            .a4-terms-box,
            .a4-payments-table th,
            .a4-payments-table td,
            .a4-stamp {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
    </style>
</head>
<body>

    <!-- Toolbar Pengendali Cetak (Hanya tampil di layar monitor / disembunyikan saat dicetak) -->
    <header class="print-toolbar no-print">
        <div class="print-toolbar-group">
            <a href="{{ route('orders.show', $order) }}" class="btn btn-secondary btn-sm" title="Kembali ke halaman detail pesanan">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                <span>Kembali ke Detail Pesanan</span>
            </a>

            <!-- Pemilih Format Cetak Cepat -->
            <div style="display: flex; gap: 4px; margin-left: 10px;">
                <a href="{{ route('orders.print', [$order, 'format' => 'thermal']) }}"
                   class="format-toggle-btn {{ $currentFormat === 'thermal' ? 'active' : '' }}"
                   title="Beralih ke format struk thermal kasir (58mm/80mm)">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M6 2h12a2 2 0 0 1 2 2v16l-3-2-3 2-3-2-3 2-2-1.5V4a2 2 0 0 1 2-2z"/>
                    </svg>
                    <span>Struk Thermal (58/80mm)</span>
                </a>

                <a href="{{ route('orders.print', [$order, 'format' => 'a4']) }}"
                   class="format-toggle-btn {{ $currentFormat === 'a4' ? 'active' : '' }}"
                   title="Beralih ke format faktur kertas standar A4">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                    </svg>
                    <span>Faktur Standar (A4)</span>
                </a>
            </div>
        </div>

        <div class="print-toolbar-group">
            <span style="font-size: 12px; color: #64748b;">
                Pesanan: <strong>{{ $order->order_number }}</strong> &bull; Status: <strong>{{ $financials['payment_status_label'] }}</strong>
            </span>

            <button type="button" class="btn btn-primary btn-sm" onclick="window.print()" title="Cetak nota sekarang">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 6 2 18 2 18 9"></polyline>
                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                    <rect x="6" y="14" width="12" height="8"></rect>
                </svg>
                <span>Cetak Nota Sekarang</span>
            </button>
        </div>
    </header>

    <!-- Area Lembar Kertas Pratinjau Cetak -->
    <main class="print-viewport" id="print-area">
        @if ($currentFormat === 'thermal')
            <div class="paper-sheet paper-sheet-thermal">
                @include('orders.partials.receipt-thermal')
            </div>
        @else
            <div class="paper-sheet paper-sheet-a4">
                @include('orders.partials.receipt-a4')
            </div>
        @endif
    </main>

    @if ($autoPrint)
        <script>
            window.addEventListener('load', function () {
                window.print();
            });
        </script>
    @endif
</body>
</html>
