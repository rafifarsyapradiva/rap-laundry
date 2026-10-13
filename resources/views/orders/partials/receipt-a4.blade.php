{{-- =================================================================
     PARTIAL NOTA CETAK FORMAT FAKTUR STANDAR A4
     Sesuai Standar Offline-First, Format Dokumen Formal & Proporsional
     ================================================================= --}}
<div class="a4-receipt">
    <!-- Header Dokumen / Kop Usaha -->
    <div class="a4-header">
        <div class="a4-business-col">
            <h1 class="a4-business-name">{{ $business['name'] }}</h1>
            <p class="a4-business-desc">Layanan Cuci, Pengeringan, dan Penyetrikaan Pakaian</p>
            <div class="a4-business-meta">
                <div>{{ $business['address'] }}</div>
                <div>Telepon / WhatsApp: <strong>{{ $business['phone'] }}</strong></div>
                @if (!empty($business['operating_hours']))
                    <div>Jam Operasional: {{ $business['operating_hours'] }}</div>
                @endif
            </div>
        </div>

        <div class="a4-invoice-col">
            <div class="a4-invoice-title">FAKTUR LAUNDRY</div>
            <div class="a4-order-number">{{ $order->order_number }}</div>

            <div class="a4-status-badges">
                <span class="a4-badge a4-badge-laundry">{{ $financials['laundry_status_label'] }}</span>
                <span class="a4-badge {{ $financials['is_paid'] ? 'a4-badge-paid' : 'a4-badge-unpaid' }}">
                    {{ $financials['payment_status_label'] }}
                </span>
            </div>

            <div class="a4-meta-dates">
                <div>Tanggal Masuk: <strong>{{ $timestamps['received_at'] }}</strong></div>
                <div>Target Selesai: <strong>{{ $timestamps['estimated_completion_at'] }}</strong></div>
                @if ($order->completed_at)
                    <div>Selesai Aktual: <strong>{{ $timestamps['completed_at'] }}</strong></div>
                @endif
            </div>
        </div>
    </div>

    <!-- Banner Peringatan Jika Pesanan Dibatalkan -->
    @if ($order->is_cancelled)
        <div class="a4-cancelled-alert">
            <div class="a4-cancelled-title">PERINGATAN: PESANAN DIBATALKAN</div>
            <div class="a4-cancelled-desc">
                Dibatalkan oleh: <strong>{{ $order->canceller?->name ?? 'Administrator' }}</strong> pada {{ $timestamps['cancelled_at'] ?? '-' }}.
                @if ($order->cancellation_reason)
                    <br>Alasan Pembatalan: <em>{{ $order->cancellation_reason }}</em>
                @endif
            </div>
        </div>
    @endif

    <!-- Kartu Informasi Pelanggan & Petugas -->
    <div class="a4-meta-grid">
        <div class="a4-meta-card">
            <div class="a4-meta-card-title">Informasi Pelanggan</div>
            <table class="a4-meta-table">
                <tr>
                    <td style="width: 110px;">Nama Pelanggan</td>
                    <td style="width: 10px;">:</td>
                    <td><strong>{{ $customer['name'] }}</strong></td>
                </tr>
                <tr>
                    <td>Kode Pelanggan</td>
                    <td>:</td>
                    <td>{{ $customer['customer_code'] }}</td>
                </tr>
                <tr>
                    <td>No. Telepon</td>
                    <td>:</td>
                    <td>{{ $customer['phone'] }}</td>
                </tr>
                <tr>
                    <td>Alamat</td>
                    <td>:</td>
                    <td>{{ $customer['address'] }}</td>
                </tr>
            </table>
        </div>

        <div class="a4-meta-card">
            <div class="a4-meta-card-title">Informasi Layanan & Petugas</div>
            <table class="a4-meta-table">
                <tr>
                    <td style="width: 120px;">Petugas Penerima</td>
                    <td style="width: 10px;">:</td>
                    <td><strong>{{ $operator['name'] }}</strong> ({{ $operator['role'] }})</td>
                </tr>
                <tr>
                    <td>Status Pengerjaan</td>
                    <td>:</td>
                    <td><strong>{{ $financials['laundry_status_label'] }}</strong></td>
                </tr>
                <tr>
                    <td>Status Pembayaran</td>
                    <td>:</td>
                    <td><strong>{{ $financials['payment_status_label'] }}</strong></td>
                </tr>
                <tr>
                    <td>Waktu Cetak Nota</td>
                    <td>:</td>
                    <td>{{ $timestamps['printed_at'] }}</td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Tabel Rincian Item Layanan (Snapshot Data) -->
    <div class="a4-table-container">
        <table class="a4-items-table">
            <thead>
                <tr>
                    <th style="width: 40px; text-align: center;">No</th>
                    <th style="text-align: left;">Nama Layanan Laundry</th>
                    <th style="width: 80px; text-align: center;">Satuan</th>
                    <th style="width: 110px; text-align: right;">Kuantitas / Berat</th>
                    <th style="width: 120px; text-align: right;">Tarif Satuan</th>
                    <th style="width: 130px; text-align: right;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($items as $index => $item)
                    <tr>
                        <td style="text-align: center;">{{ $index + 1 }}</td>
                        <td>
                            <div class="a4-item-name">{{ $item['service_name'] }}</div>
                            @if (!empty($item['notes']))
                                <div class="a4-item-note">Catatan: {{ $item['notes'] }}</div>
                            @endif
                        </td>
                        <td style="text-align: center;">{{ $item['billing_unit'] }}</td>
                        <td style="text-align: right; font-weight: 600;">{{ $item['quantity_formatted'] }}</td>
                        <td style="text-align: right;">{{ $item['unit_price_formatted'] }}</td>
                        <td style="text-align: right; font-weight: 600;">{{ $item['subtotal_formatted'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Baris Bawah: Riwayat Pembayaran & Ringkasan Finansial -->
    <div class="a4-bottom-grid">
        <!-- Kolom Kiri: Riwayat Pembayaran & Catatan -->
        <div class="a4-bottom-left">
            <!-- Riwayat Pembayaran Valid -->
            <div class="a4-section-box">
                <div class="a4-section-title">Riwayat Pembayaran Diterima</div>
                @if (count($payments) > 0)
                    <table class="a4-payments-table">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Metode</th>
                                <th>Petugas</th>
                                <th style="text-align: right;">Nominal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($payments as $payment)
                                <tr>
                                    <td>{{ $payment['payment_date'] }}</td>
                                    <td>
                                        <strong>{{ $payment['method'] }}</strong>
                                        @if (!empty($payment['reference']))
                                            <span style="font-size: 10px; color: #64748b;">(Ref: {{ $payment['reference'] }})</span>
                                        @endif
                                    </td>
                                    <td>{{ $payment['recorder_name'] }}</td>
                                    <td style="text-align: right; font-weight: 600;">{{ $payment['amount_formatted'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    @if ($cash_summary)
                        <div class="a4-cash-tendered-info">
                            Pembayaran Tunai Terakhir: Uang Diterima <strong>{{ $cash_summary['cash_tendered_formatted'] }}</strong>,
                            Kembalian <strong>{{ $cash_summary['change_amount_formatted'] }}</strong>.
                        </div>
                    @endif
                @else
                    <div class="a4-no-payment-notice">
                        Belum ada pembayaran yang tercatat untuk pesanan ini.
                    </div>
                @endif
            </div>

            <!-- Catatan Khusus Pesanan -->
            @if (!empty($order->notes))
                <div class="a4-section-box" style="margin-top: 10px;">
                    <div class="a4-section-title">Catatan Khusus Pesanan</div>
                    <div style="font-size: 11px; color: #334155;">{{ $order->notes }}</div>
                </div>
            @endif

            <!-- Ketentuan Usaha -->
            <div class="a4-terms-box">
                <div class="a4-terms-title">Syarat & Ketentuan Pengambilan Pakaian:</div>
                <div class="a4-terms-content">{{ $business['footer_note'] }}</div>
            </div>
        </div>

        <!-- Kolom Kanan: Rincian Kalkulasi Finansial & Stempel -->
        <div class="a4-bottom-right">
            <table class="a4-summary-table">
                <tr>
                    <td>Subtotal Layanan</td>
                    <td style="text-align: right;">{{ $financials['subtotal_formatted'] }}</td>
                </tr>

                @if ($financials['has_discount'])
                    <tr>
                        <td>Potongan Diskon</td>
                        <td style="text-align: right; color: #dc2626;">- {{ $financials['discount_amount_formatted'] }}</td>
                    </tr>
                @endif

                @if ($financials['has_extra_charge'])
                    <tr>
                        <td>Biaya Tambahan</td>
                        <td style="text-align: right;">+ {{ $financials['extra_charge_formatted'] }}</td>
                    </tr>
                @endif

                <tr class="a4-summary-total-row">
                    <td>TOTAL TAGIHAN</td>
                    <td style="text-align: right;">{{ $financials['total_amount_formatted'] }}</td>
                </tr>

                <tr>
                    <td>Total Uang Diterima</td>
                    <td style="text-align: right; color: #16a34a; font-weight: 600;">{{ $financials['total_paid_formatted'] }}</td>
                </tr>

                <tr class="a4-summary-balance-row">
                    <td>SALDO SISA TAGIHAN</td>
                    <td style="text-align: right; color: {{ $financials['is_paid'] ? '#16a34a' : '#dc2626' }};">
                        {{ $financials['remaining_balance_formatted'] }}
                    </td>
                </tr>
            </table>

            <!-- Stempel Status Lunas / Belum Lunas -->
            <div class="a4-stamp-wrapper">
                @if ($financials['is_paid'])
                    <div class="a4-stamp a4-stamp-paid">
                        <span>LUNAS</span>
                    </div>
                @else
                    <div class="a4-stamp a4-stamp-unpaid">
                        <span>BELUM LUNAS</span>
                        <div style="font-size: 10px; font-weight: normal; margin-top: 2px;">Sisa: {{ $financials['remaining_balance_formatted'] }}</div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Area Tanda Tangan Serah Terima -->
    <div class="a4-signatures-grid">
        <div class="a4-sign-box">
            <div>Pelanggan,</div>
            <div class="a4-sign-line"></div>
            <div>( {{ $customer['name'] }} )</div>
        </div>

        <div class="a4-sign-box">
            <div>Kasir / Petugas Penerima,</div>
            <div class="a4-sign-line"></div>
            <div>( {{ $operator['name'] }} )</div>
        </div>
    </div>
</div>
