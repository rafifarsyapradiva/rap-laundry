<?php

namespace Database\Seeders;

use App\Models\BusinessSetting;
use Illuminate\Database\Seeder;

class BusinessSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            BusinessSetting::KEY_BUSINESS_NAME => 'RAP Laundry',
            BusinessSetting::KEY_BUSINESS_ADDRESS => 'Jl. Kebon Jeruk No. 12, Jakarta Barat',
            BusinessSetting::KEY_BUSINESS_PHONE => '081234567890',
            BusinessSetting::KEY_BUSINESS_LOGO_PATH => null,
            BusinessSetting::KEY_ORDER_NUMBER_PREFIX => 'LD',
            BusinessSetting::KEY_RECEIPT_FOOTER_NOTE => 'Harap bawa nota ini saat pengambilan pakaian. Pakaian yang tidak diambil dalam 30 hari di luar tanggung jawab laundry.',
            BusinessSetting::KEY_DEFAULT_ESTIMATED_HOURS => '48',
            BusinessSetting::KEY_PRINT_FORMAT => 'a4',
            BusinessSetting::KEY_OPERATING_HOURS => 'Senin - Sabtu: 08.00 - 20.00 WIB',
        ];

        foreach ($settings as $key => $value) {
            BusinessSetting::updateOrCreate(
                ['setting_key' => $key],
                ['setting_value' => $value]
            );
        }
    }
}
