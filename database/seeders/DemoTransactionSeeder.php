<?php

namespace Database\Seeders;

use App\Enums\BillingUnit;
use App\Enums\LaundryStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentRecordStatus;
use App\Enums\PaymentStatus;
use App\Models\Customer;
use App\Models\NumberSequence;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoTransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('username', 'admin')->first();
        $kasir = User::where('username', 'kasir')->first();

        // 1. Data Pelanggan Contoh
        $customer1 = Customer::updateOrCreate(
            ['customer_code' => 'CUST-0001'],
            [
                'name' => 'Budi Santoso',
                'phone' => '081298765432',
                'address' => 'Jl. Kemanggisan Ilir No. 45, Palmerah',
                'notes' => 'Langganan mingguan, suka deterjen ekstra wangi.',
                'is_active' => true,
            ]
        );

        $customer2 = Customer::updateOrCreate(
            ['customer_code' => 'CUST-0002'],
            [
                'name' => 'Siti Rahma',
                'phone' => '081312345678',
                'address' => 'Apartemen Mediterania Tower A Lt. 12',
                'notes' => 'Pakaian kantor, jangan dicampur pemutih.',
                'is_active' => true,
            ]
        );

        $customer3 = Customer::updateOrCreate(
            ['customer_code' => 'CUST-0003'],
            [
                'name' => 'Ahmad Fauzi',
                'phone' => '085678901234',
                'address' => 'Kost Palem No. 7, Rawa Belong',
                'notes' => null,
                'is_active' => true,
            ]
        );

        NumberSequence::updateOrCreate(
            ['sequence_key' => 'customer', 'period' => 'all'],
            ['last_value' => 3]
        );

        // Ambil Layanan
        $cuciSetrika = Service::where('name', 'Cuci Setrika')->first();
        $cuciKering = Service::where('name', 'Cuci Kering')->first();
        $bedCover = Service::where('name', 'Bed Cover Besar')->first();
        $setrikaSaja = Service::where('name', 'Setrika Saja')->first();

        $today = now()->format('Ymd');

        // 2. Transaksi 1: Budi Santoso - Selesai & Lunas (Tunai)
        $order1Number = "LD-{$today}-0001";
        $order1 = Order::updateOrCreate(
            ['order_number' => $order1Number],
            [
                'customer_id' => $customer1->id,
                'created_by' => $kasir->id,
                'received_at' => now()->subDays(2),
                'estimated_completion_at' => now()->subDay(),
                'completed_at' => now(),
                'subtotal' => '24000.00',
                'discount_amount' => '0.00',
                'extra_charge' => '0.00',
                'total_amount' => '24000.00',
                'notes' => 'Pakaian seragam kerja.',
                'laundry_status' => LaundryStatus::Selesai,
                'payment_status' => PaymentStatus::Lunas,
            ]
        );

        OrderItem::updateOrCreate(
            ['order_id' => $order1->id, 'service_name_snapshot' => $cuciSetrika?->name ?? 'Cuci Setrika'],
            [
                'service_id' => $cuciSetrika?->id,
                'billing_unit_snapshot' => BillingUnit::Kg,
                'quantity' => '3.00',
                'unit_price' => '8000.00',
                'subtotal' => '24000.00',
                'notes' => '3 kg pakaian campur',
            ]
        );

        Payment::updateOrCreate(
            ['order_id' => $order1->id, 'amount' => '24000.00'],
            [
                'recorded_by' => $kasir->id,
                'payment_date' => now()->subDays(2),
                'method' => PaymentMethod::Tunai,
                'reference' => null,
                'notes' => 'Pembayaran lunas di awal saat serah terima pakaian.',
                'status' => PaymentRecordStatus::Valid,
            ]
        );

        OrderStatusHistory::updateOrCreate(
            ['order_id' => $order1->id, 'to_status' => LaundryStatus::Diterima->value],
            [
                'from_status' => null,
                'changed_by' => $kasir->id,
                'note' => 'Pesanan baru diterima kasir.',
                'created_at' => now()->subDays(2),
            ]
        );

        OrderStatusHistory::updateOrCreate(
            ['order_id' => $order1->id, 'to_status' => LaundryStatus::SiapDiambil->value],
            [
                'from_status' => LaundryStatus::Diterima->value,
                'changed_by' => $kasir->id,
                'note' => 'Cucian selesai disetrika dan dikemas rapi.',
                'created_at' => now()->subDay(),
            ]
        );

        OrderStatusHistory::updateOrCreate(
            ['order_id' => $order1->id, 'to_status' => LaundryStatus::Selesai->value],
            [
                'from_status' => LaundryStatus::SiapDiambil->value,
                'changed_by' => $kasir->id,
                'note' => 'Pakaian telah diserahkan kepada pelanggan.',
                'created_at' => now(),
            ]
        );

        // 3. Transaksi 2: Siti Rahma - Siap Diambil, Dibayar Sebagian (DP via Transfer Bank)
        $order2Number = "LD-{$today}-0002";
        $order2 = Order::updateOrCreate(
            ['order_number' => $order2Number],
            [
                'customer_id' => $customer2->id,
                'created_by' => $kasir->id,
                'received_at' => now()->subDay(),
                'estimated_completion_at' => now()->addHours(12),
                'completed_at' => null,
                'subtotal' => '52000.00',
                'discount_amount' => '0.00',
                'extra_charge' => '0.00',
                'total_amount' => '52000.00',
                'notes' => 'Bed cover wangi lavender.',
                'laundry_status' => LaundryStatus::SiapDiambil,
                'payment_status' => PaymentStatus::DibayarSebagian,
            ]
        );

        OrderItem::updateOrCreate(
            ['order_id' => $order2->id, 'service_name_snapshot' => $cuciKering?->name ?? 'Cuci Kering'],
            [
                'service_id' => $cuciKering?->id,
                'billing_unit_snapshot' => BillingUnit::Kg,
                'quantity' => '4.50',
                'unit_price' => '6000.00',
                'subtotal' => '27000.00',
                'notes' => '4,5 kg pakaian sehari-hari',
            ]
        );

        OrderItem::updateOrCreate(
            ['order_id' => $order2->id, 'service_name_snapshot' => $bedCover?->name ?? 'Bed Cover Besar'],
            [
                'service_id' => $bedCover?->id,
                'billing_unit_snapshot' => BillingUnit::Item,
                'quantity' => '1.00',
                'unit_price' => '25000.00',
                'subtotal' => '25000.00',
                'notes' => 'Bed cover king size',
            ]
        );

        Payment::updateOrCreate(
            ['order_id' => $order2->id, 'amount' => '30000.00'],
            [
                'recorded_by' => $kasir->id,
                'payment_date' => now()->subDay(),
                'method' => PaymentMethod::TransferBank,
                'reference' => 'TRF-BCA-98124',
                'notes' => 'DP via transfer bank BCA, sisa tagihan Rp22.000 saat pengambilan.',
                'status' => PaymentRecordStatus::Valid,
            ]
        );

        OrderStatusHistory::updateOrCreate(
            ['order_id' => $order2->id, 'to_status' => LaundryStatus::Diterima->value],
            [
                'from_status' => null,
                'changed_by' => $kasir->id,
                'note' => 'Pesanan diterima dengan DP Rp30.000.',
                'created_at' => now()->subDay(),
            ]
        );

        OrderStatusHistory::updateOrCreate(
            ['order_id' => $order2->id, 'to_status' => LaundryStatus::SiapDiambil->value],
            [
                'from_status' => LaundryStatus::Diterima->value,
                'changed_by' => $kasir->id,
                'note' => 'Pakaian dan bed cover telah bersih dan siap diambil.',
                'created_at' => now(),
            ]
        );

        // 4. Transaksi 3: Ahmad Fauzi - Dicuci, Belum Dibayar
        $order3Number = "LD-{$today}-0003";
        $order3 = Order::updateOrCreate(
            ['order_number' => $order3Number],
            [
                'customer_id' => $customer3->id,
                'created_by' => $kasir->id,
                'received_at' => now(),
                'estimated_completion_at' => now()->addDay(),
                'completed_at' => null,
                'subtotal' => '10000.00',
                'discount_amount' => '0.00',
                'extra_charge' => '0.00',
                'total_amount' => '10000.00',
                'notes' => 'Setrika licin celana panjang.',
                'laundry_status' => LaundryStatus::Dicuci,
                'payment_status' => PaymentStatus::BelumDibayar,
            ]
        );

        OrderItem::updateOrCreate(
            ['order_id' => $order3->id, 'service_name_snapshot' => $setrikaSaja?->name ?? 'Setrika Saja'],
            [
                'service_id' => $setrikaSaja?->id,
                'billing_unit_snapshot' => BillingUnit::Kg,
                'quantity' => '2.00',
                'unit_price' => '5000.00',
                'subtotal' => '10000.00',
                'notes' => '2 kg kemeja & celana bahan',
            ]
        );

        OrderStatusHistory::updateOrCreate(
            ['order_id' => $order3->id, 'to_status' => LaundryStatus::Diterima->value],
            [
                'from_status' => null,
                'changed_by' => $kasir->id,
                'note' => 'Pesanan diterima kasir.',
                'created_at' => now(),
            ]
        );

        OrderStatusHistory::updateOrCreate(
            ['order_id' => $order3->id, 'to_status' => LaundryStatus::Dicuci->value],
            [
                'from_status' => LaundryStatus::Diterima->value,
                'changed_by' => $kasir->id,
                'note' => 'Proses pengerjaan cucian sedang berjalan.',
                'created_at' => now()->addMinutes(30),
            ]
        );

        NumberSequence::updateOrCreate(
            ['sequence_key' => 'order', 'period' => $today],
            ['last_value' => 3]
        );
    }
}
