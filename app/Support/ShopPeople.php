<?php

namespace App\Support;

use App\Models\LoyaltyWallet;
use App\Models\Order;
use App\Models\PromoCode;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ShopPeople
{
    public function customerCount(): int
    {
        return (int) Order::query()
            ->whereNotNull('customer_phone_normalized')
            ->where('customer_phone_normalized', '!=', '')
            ->selectRaw('count(distinct customer_phone_normalized) as aggregate')
            ->value('aggregate');
    }

    public function loyaltyWalletCount(): int
    {
        return LoyaltyWallet::query()->count();
    }

    public function referrerCount(): int
    {
        return (int) PromoCode::query()
            ->whereNotNull('referrer_phone_normalized')
            ->where('referrer_phone_normalized', '!=', '')
            ->selectRaw('count(distinct referrer_phone_normalized) as aggregate')
            ->value('aggregate');
    }

    public function adminCount(): int
    {
        return User::query()->count();
    }

    /**
     * One row per unique customer phone from orders.
     *
     * @return Builder<Order>
     */
    public function customersQuery(): Builder
    {
        return Order::query()
            ->fromSub(
                Order::query()
                    ->select([
                        DB::raw('MIN(id) as id'),
                        'customer_phone_normalized',
                        DB::raw('MAX(customer_name) as customer_name'),
                        DB::raw('MAX(customer_email) as customer_email'),
                        DB::raw('MAX(customer_phone) as customer_phone'),
                        DB::raw('COUNT(*) as orders_count'),
                        DB::raw('MAX(created_at) as last_ordered_at'),
                        DB::raw('SUM(total) as lifetime_total'),
                    ])
                    ->whereNotNull('customer_phone_normalized')
                    ->where('customer_phone_normalized', '!=', '')
                    ->groupBy('customer_phone_normalized'),
                'orders',
            );
    }

    /**
     * One row per unique referrer phone from promo codes.
     *
     * @return Builder<PromoCode>
     */
    public function referrersQuery(): Builder
    {
        return PromoCode::query()
            ->fromSub(
                PromoCode::query()
                    ->select([
                        DB::raw('MIN(id) as id'),
                        'referrer_phone_normalized',
                        DB::raw('MAX(referrer_name) as referrer_name'),
                        DB::raw('MAX(referrer_phone) as referrer_phone'),
                        DB::raw('COUNT(*) as promo_codes_count'),
                    ])
                    ->whereNotNull('referrer_phone_normalized')
                    ->where('referrer_phone_normalized', '!=', '')
                    ->groupBy('referrer_phone_normalized'),
                'promo_codes',
            );
    }
}
