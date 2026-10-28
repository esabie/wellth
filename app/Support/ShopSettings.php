<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class ShopSettings
{
    public const SECOND_PURCHASE_ENABLED = 'second_purchase_discount_enabled';

    public const SECOND_PURCHASE_PERCENT = 'second_purchase_discount_percent';

    public const SECOND_PURCHASE_LABEL = 'second_purchase_discount_label';

    public const POINTS_ENABLED = 'points_enabled';

    public const POINTS_PER_PAID_ORDER = 'points_per_paid_order';

    public const POINT_VALUE_GHS = 'point_value_ghs';

    public const MINIMUM_ORDER_QUANTITY = 'minimum_order_quantity';

    public const REFERRAL_POINTS_PER_ORDER = 'referral_points_per_order';

    public const ADMIN_NOTIFICATION_EMAIL = 'admin_notification_email';

    public const ADMIN_NOTIFICATION_PHONE = 'admin_notification_phone';

    private const CACHE_KEY = 'shop_settings';

    /**
     * @return array{
     *     second_purchase_discount_enabled: bool,
     *     second_purchase_discount_percent: float,
     *     second_purchase_discount_label: string,
     *     points_enabled: bool,
     *     points_per_paid_order: int,
     *     point_value_ghs: float
     * }
     */
    public function loyalty(): array
    {
        return [
            self::SECOND_PURCHASE_ENABLED => $this->boolean(
                self::SECOND_PURCHASE_ENABLED,
                (bool) config('shop.second_purchase_discount_enabled', true),
            ),
            self::SECOND_PURCHASE_PERCENT => $this->float(
                self::SECOND_PURCHASE_PERCENT,
                (float) config('shop.second_purchase_discount_percent', 5),
            ),
            self::SECOND_PURCHASE_LABEL => $this->string(
                self::SECOND_PURCHASE_LABEL,
                (string) config('shop.second_purchase_discount_label', 'Second purchase 5% off'),
            ),
            ...$this->points(),
        ];
    }

    /**
     * @return array{
     *     points_enabled: bool,
     *     points_per_paid_order: int,
     *     point_value_ghs: float
     * }
     */
    public function points(): array
    {
        return [
            self::POINTS_ENABLED => $this->boolean(
                self::POINTS_ENABLED,
                (bool) config('shop.points_enabled', false),
            ),
            self::POINTS_PER_PAID_ORDER => max(0, (int) $this->float(
                self::POINTS_PER_PAID_ORDER,
                (float) config('shop.points_per_paid_order', 10),
            )),
            self::POINT_VALUE_GHS => $this->float(
                self::POINT_VALUE_GHS,
                (float) config('shop.point_value_ghs', 0.10),
            ),
        ];
    }

    /**
     * @return array{
     *     minimum_order_quantity: int,
     *     referral_points_per_order: int,
     *     admin_notification_email: string,
     *     admin_notification_phone: string
     * }
     */
    public function checkout(): array
    {
        return [
            self::MINIMUM_ORDER_QUANTITY => max(1, (int) $this->float(
                self::MINIMUM_ORDER_QUANTITY,
                (float) config('shop.minimum_order_quantity', 10),
            )),
            self::REFERRAL_POINTS_PER_ORDER => max(0, (int) $this->float(
                self::REFERRAL_POINTS_PER_ORDER,
                (float) config('shop.referral_points_per_order', 10),
            )),
            self::ADMIN_NOTIFICATION_EMAIL => $this->string(
                self::ADMIN_NOTIFICATION_EMAIL,
                (string) config('shop.admin_notification_email', ''),
            ),
            self::ADMIN_NOTIFICATION_PHONE => $this->string(
                self::ADMIN_NOTIFICATION_PHONE,
                (string) config('shop.admin_notification_phone', ''),
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function putCheckout(array $values): void
    {
        $current = $this->checkout();

        Setting::query()->updateOrCreate(
            ['key' => self::MINIMUM_ORDER_QUANTITY],
            [
                'value' => (string) max(
                    1,
                    (int) ($values[self::MINIMUM_ORDER_QUANTITY] ?? $current[self::MINIMUM_ORDER_QUANTITY]),
                ),
            ],
        );

        Setting::query()->updateOrCreate(
            ['key' => self::REFERRAL_POINTS_PER_ORDER],
            [
                'value' => (string) max(
                    0,
                    (int) ($values[self::REFERRAL_POINTS_PER_ORDER] ?? $current[self::REFERRAL_POINTS_PER_ORDER]),
                ),
            ],
        );

        if (array_key_exists(self::ADMIN_NOTIFICATION_EMAIL, $values)) {
            Setting::query()->updateOrCreate(
                ['key' => self::ADMIN_NOTIFICATION_EMAIL],
                ['value' => trim((string) $values[self::ADMIN_NOTIFICATION_EMAIL])],
            );
        }

        if (array_key_exists(self::ADMIN_NOTIFICATION_PHONE, $values)) {
            Setting::query()->updateOrCreate(
                ['key' => self::ADMIN_NOTIFICATION_PHONE],
                ['value' => trim((string) $values[self::ADMIN_NOTIFICATION_PHONE])],
            );
        }

        $this->forgetCache();
    }

    public function minimumOrderQuantity(): int
    {
        return $this->checkout()[self::MINIMUM_ORDER_QUANTITY];
    }

    public function referralPointsPerOrder(): int
    {
        return $this->checkout()[self::REFERRAL_POINTS_PER_ORDER];
    }

    public function adminNotificationEmail(): ?string
    {
        $email = trim($this->checkout()[self::ADMIN_NOTIFICATION_EMAIL]);

        return $email !== '' ? $email : null;
    }

    public function adminNotificationPhone(): ?string
    {
        $phone = trim($this->checkout()[self::ADMIN_NOTIFICATION_PHONE]);

        return $phone !== '' ? $phone : null;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function putLoyalty(array $values): void
    {
        Setting::query()->updateOrCreate(
            ['key' => self::SECOND_PURCHASE_ENABLED],
            ['value' => ((bool) ($values[self::SECOND_PURCHASE_ENABLED] ?? false)) ? '1' : '0'],
        );

        Setting::query()->updateOrCreate(
            ['key' => self::SECOND_PURCHASE_PERCENT],
            ['value' => (string) max(0, (float) ($values[self::SECOND_PURCHASE_PERCENT] ?? 0))],
        );

        Setting::query()->updateOrCreate(
            ['key' => self::SECOND_PURCHASE_LABEL],
            [
                'value' => trim((string) ($values[self::SECOND_PURCHASE_LABEL] ?? ''))
                    ?: 'Second purchase discount',
            ],
        );

        Setting::query()->updateOrCreate(
            ['key' => self::POINTS_ENABLED],
            ['value' => ((bool) ($values[self::POINTS_ENABLED] ?? false)) ? '1' : '0'],
        );

        Setting::query()->updateOrCreate(
            ['key' => self::POINTS_PER_PAID_ORDER],
            ['value' => (string) max(0, (int) ($values[self::POINTS_PER_PAID_ORDER] ?? 0))],
        );

        Setting::query()->updateOrCreate(
            ['key' => self::POINT_VALUE_GHS],
            ['value' => (string) max(0, (float) ($values[self::POINT_VALUE_GHS] ?? 0))],
        );

        $this->forgetCache();
    }

    public function boolean(string $key, bool $default = false): bool
    {
        $value = $this->all()[$key] ?? null;

        if ($value === null) {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public function float(string $key, float $default = 0): float
    {
        $value = $this->all()[$key] ?? null;

        if ($value === null || $value === '') {
            return $default;
        }

        return (float) $value;
    }

    public function string(string $key, string $default = ''): string
    {
        $value = $this->all()[$key] ?? null;

        if ($value === null || $value === '') {
            return $default;
        }

        return (string) $value;
    }

    public function put(string $key, mixed $value): void
    {
        Setting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value],
        );

        $this->forgetCache();
    }

    /**
     * @return array<string, string|null>
     */
    public function all(): array
    {
        if (! Schema::hasTable('settings')) {
            return [];
        }

        return Cache::rememberForever(self::CACHE_KEY, function (): array {
            return Setting::query()
                ->pluck('value', 'key')
                ->all();
        });
    }

    public function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
