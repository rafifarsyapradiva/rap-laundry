<?php

namespace Database\Seeders;

use App\Enums\BillingUnit;
use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $services = [
            [
                'name' => 'Cuci Kering',
                'description' => 'Pencucian pakaian dan pengeringan mesin tanpa setrika.',
                'billing_unit' => BillingUnit::Kg,
                'price' => '6000.00',
                'estimated_hours' => 24,
                'is_active' => true,
            ],
            [
                'name' => 'Cuci Setrika',
                'description' => 'Pencucian pakaian lengkap, pengeringan, dan setrika rapi.',
                'billing_unit' => BillingUnit::Kg,
                'price' => '8000.00',
                'estimated_hours' => 48,
                'is_active' => true,
            ],
            [
                'name' => 'Setrika Saja',
                'description' => 'Penyetrikaan pakaian rapi dan wangi (tanpa cuci).',
                'billing_unit' => BillingUnit::Kg,
                'price' => '5000.00',
                'estimated_hours' => 24,
                'is_active' => true,
            ],
            [
                'name' => 'Bed Cover Besar',
                'description' => 'Pencucian bed cover ukuran king/queen per helai.',
                'billing_unit' => BillingUnit::Item,
                'price' => '25000.00',
                'estimated_hours' => 48,
                'is_active' => true,
            ],
            [
                'name' => 'Selimut Tebal',
                'description' => 'Pencucian selimut per helai.',
                'billing_unit' => BillingUnit::Item,
                'price' => '15000.00',
                'estimated_hours' => 24,
                'is_active' => true,
            ],
            [
                'name' => 'Sepatu',
                'description' => 'Pencucian sepatu casual atau sneakers per pasang.',
                'billing_unit' => BillingUnit::Item,
                'price' => '30000.00',
                'estimated_hours' => 72,
                'is_active' => true,
            ],
            [
                'name' => 'Layanan Express 6 Jam',
                'description' => 'Paket pengerjaan kilat selesai dalam 6 jam operasional.',
                'billing_unit' => BillingUnit::Paket,
                'price' => '20000.00',
                'estimated_hours' => 6,
                'is_active' => true,
            ],
        ];

        foreach ($services as $service) {
            Service::updateOrCreate(
                ['name' => $service['name']],
                $service
            );
        }
    }
}
