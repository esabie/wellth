<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->replace('sms_template_order_paid_customer', [
            'fresh shots' => 'fresh blends',
            '7 days' => '72 hours',
        ]);

        $this->replace('sms_template_order_delivered_customer', [
            'Wellth shots' => 'Wellth blend',
        ]);

        Cache::forget('shop_settings');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->replace('sms_template_order_paid_customer', [
            'fresh blends' => 'fresh shots',
            '72 hours' => '7 days',
        ]);

        $this->replace('sms_template_order_delivered_customer', [
            'Wellth blend' => 'Wellth shots',
        ]);

        Cache::forget('shop_settings');
    }

    /**
     * @param  array<string, string>  $replacements
     */
    private function replace(string $key, array $replacements): void
    {
        $value = DB::table('settings')->where('key', $key)->value('value');

        if (! is_string($value)) {
            return;
        }

        DB::table('settings')->where('key', $key)->update([
            'value' => strtr($value, $replacements),
            'updated_at' => now(),
        ]);
    }
};
