{{-- =================================================================
     PARTIAL NOTA CETAK FORMAT STRUK THERMAL (POS 58mm / 80mm)
     Sesuai Standar Offline-First, Monokrom, Kontras Tinggi & Ramah Kasir
     ================================================================= --}}
<div class="thermal-receipt">
    <!-- Header Identitas Usaha -->
    <div class="thermal-header">
        @if (!empty($business['logo_url']))
            <div style="margin-bottom: 6px;">
                <img src="{{ $business['logo_url'] }}" alt="{{ $business['name'] }}" style="max-height: 48px; max-width: 100px; filter: grayscale(100%); object-fit: contain;">
            </div>
        @endif
        <h2 class="thermal-business-name">{{ $business['name'] }}</h2>
        <div class="thermal-business-info">{{ $business['address'] }}</div>
        <div class="thermal-business-info">Telp: {{ $business['phone'] }}</div>
        @if (!empty($business['operating_hours']))
            <div class="thermal-business-info">{{ $business['operating_hours'] }}</div>
        @endif
    </div>

    <div class="thermal-divider">================================</div>

    <!-- Metadata Transaksi & Pelanggan -->
    <div class="thermal-meta">
        <div class="thermal-row">
            <span>No. Transaksi</span>
            <strong>{{ $order->order_number }}</strong>
        </div>
        <div class="thermal-row">
            <span>Waktu Masuk</span>
            <span>{{ $timestamps['received_at'] }}</span>
        </div>
        <div class="thermal-row">
            <span>Estimasi Selesai</span>
            <span>{{ $timestamps['estimated_completion_at'] }}</span>
        </div>
        <div class="thermal-row">
            <span>Kasir</span>
            <span>{{ $operator['name'] }}</span>
        </div>
        <div class="thermal-divider-dotted">--------------------------------</div>
        <div class="thermal-row">
            <span>Pelanggan</span>
            <strong>{{ $customer['name'] }}</strong>
        </div>
        <div class="thermal-row">
            <span>No. Telp</span>
            <span>{{ $customer['phone'] }}</span>
        </div>
        <div class="thermal-row">
            <span>Status Cucian</span>
            <strong>{{ $financials['laundry_status_label'] }}</strong>
        </div>
        <div class="thermal-row">
            <span>Status Bayar</span>
            <strong>{{ $financials['payment_status_label'] }}</strong>
        </div>
    </div>

    <!-- Banner Khusus Jika Dibatalkan -->
    @if ($order->is_cancelled)
        <div class="thermal-divider-dotted">--------------------------------</div>
        <div class="thermal-cancelled-box">
            *** PESANAN DIBATALKAN ***
            @if ($order->cancellation_reason)
                <div style="font-size: 11px; margin-top: 2px;">Alasan: {{ $order->cancellation_reason }}</div>
            @endif
        </div>
    @endif

    <div class="thermal-divider">================================</div>

    <!-- Rincian Item Layanan (Snapshot Data) -->
    <div class="thermal-items">
        <div class="thermal-row thermal-table-head">
            <span>ITEM LAYANAN</span>
            <span>SUBTOTAL</span>
        </div>
        <div class="thermal-divider-dotted">--------------------------------</div>
        @foreach ($items as $item)
            <div class="thermal-item-block">
                <div class="thermal-item-name">{{ $item['service_name'] }}</div>
                <div class="thermal-row thermal-item-calc">
                    <span>{{ $item['quantity_formatted'] }} {{ $item['billing_unit'] }} &times; {{ $item['unit_price_formatted'] }}</span>
                    <span>{{ $item['subtotal_formatted'] }}</span>
                </div>
                @if (!empty($item['notes']))
                    <div class="thermal-item-note">* Catatan: {{ $item['notes'] }}</div>
                @endif
            </div>
        @endforeach
    </div>

    <div class="thermal-divider">================================</div>

    <!-- Rincian Finansial & Kalkulasi Gate 8 / Gate 9 -->
    <div class="thermal-financials">
        <div class="thermal-row">
            <span>Subtotal</span>
            <span>{{ $financials['subtotal_formatted'] }}</span>
        </div>

        @if ($financials['has_discount'])
            <div class="thermal-row">
                <span>Diskon</span>
                <span>- {{ $financials['discount_amount_formatted'] }}</span>
            </div>
        @endif

        @if ($financials['has_extra_charge'])
            <div class="thermal-row">
                <span>Biaya Tambahan</span>
                <span>+ {{ $financials['extra_charge_formatted'] }}</span>
            </div>
        @endif

        <div class="thermal-divider-dotted">--------------------------------</div>

        <div class="thermal-row thermal-total-row">
            <span>TOTAL TAGIHAN</span>
            <strong>{{ $financials['total_amount_formatted'] }}</strong>
        </div>

        <div class="thermal-row">
            <span>Total Pembayaran</span>
            <span>{{ $financials['total_paid_formatted'] }}</span>
        </div>

        @if ($cash_summary)
            <div class="thermal-row">
                <span>Tunai Diterima</span>
                <span>{{ $cash_summary['cash_tendered_formatted'] }}</span>
            </div>
            <div class="thermal-row">
                <span>Kembalian</span>
                <span>{{ $cash_summary['change_amount_formatted'] }}</span>
            </div>
        @endif

        <div class="thermal-row thermal-balance-row">
            <span>SISA TAGIHAN</span>
            <strong>{{ $financials['remaining_balance_formatted'] }}</strong>
        </div>
    </div>

    <!-- Stempel Status Finansial -->
    <div class="thermal-stamp-box">
        @if ($financials['is_paid'])
            <div class="thermal-stamp thermal-stamp-paid">*** LUNAS ***</div>
        @else
            <div class="thermal-stamp thermal-stamp-unpaid">*** BELUM LUNAS ***</div>
            <div style="font-size: 11px; margin-top: 2px;">Sisa: {{ $financials['remaining_balance_formatted'] }}</div>
        @endif
    </div>

    @if (!empty($order->notes))
        <div class="thermal-divider-dotted">--------------------------------</div>
        <div class="thermal-order-notes">
            <strong>Catatan Pesanan:</strong><br>
            {{ $order->notes }}
        </div>
    @endif

    <div class="thermal-divider">================================</div>

    <!-- Catatan Kaki & Syarat Pengambilan Usaha -->
    <div class="thermal-footer">
        <div class="thermal-footer-note">{{ $business['footer_note'] }}</div>
        <div class="thermal-thank-you">Terima kasih atas kunjungan Anda!</div>
        <div class="thermal-print-time">Dicetak: {{ $timestamps['printed_at'] }}</div>
    </div>
</div>
