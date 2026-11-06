<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            [
                'category' => 'general',
                'key' => 'app_name',
                'value' => 'Kerefa Restaurant ERP',
                'type' => 'string',
                'description' => 'System application display name',
            ],
            [
                'category' => 'general',
                'key' => 'currency_code',
                'value' => 'ETB',
                'type' => 'string',
                'description' => 'Base currency code',
            ],
            [
                'category' => 'general',
                'key' => 'currency_symbol',
                'value' => 'Br',
                'type' => 'string',
                'description' => 'Base currency symbol display',
            ],
            [
                'category' => 'pos',
                'key' => 'bill_generation_enabled',
                'value' => '0',
                'type' => 'boolean',
                'description' => 'Customer bill/receipt printing generation feature switch (strictly disabled in initial release)',
            ],
            [
                'category' => 'pos',
                'key' => 'vat_rate',
                'value' => '15.00',
                'type' => 'string',
                'description' => 'Standard VAT rate percentage',
            ],
            [
                'category' => 'pos',
                'key' => 'service_charge_rate',
                'value' => '10.00',
                'type' => 'string',
                'description' => 'Standard restaurant service charge percentage',
            ],
            [
                'category' => 'general',
                'key' => 'timezone',
                'value' => 'Africa/Addis_Ababa',
                'type' => 'string',
                'description' => 'System default timezone',
            ],
        ];

        foreach ($settings as $setting) {
            SystemSetting::updateOrCreate(
                ['branch_id' => null, 'key' => $setting['key']],
                $setting
            );
        }
    }
}
