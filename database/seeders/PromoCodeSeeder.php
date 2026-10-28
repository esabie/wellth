<?php

namespace Database\Seeders;

use App\Enums\PromoCodeType;
use App\Models\PromoCode;
use Illuminate\Database\Seeder;

class PromoCodeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PromoCode::query()->updateOrCreate(
            ['code' => 'WELLTH10'],
            [
                'type' => PromoCodeType::Percent,
                'value' => 10,
                'label' => '10% off',
                'min_order_amount' => null,
                'max_uses' => null,
                'is_active' => true,
            ],
        );
    }
}
