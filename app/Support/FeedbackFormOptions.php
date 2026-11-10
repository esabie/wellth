<?php

namespace App\Support;

class FeedbackFormOptions
{
    /**
     * @return array<int, string>
     */
    public static function shoppingRatings(): array
    {
        return [
            1 => 'Frustrating',
            2 => 'Okay',
            3 => 'Fine',
            4 => 'Smooth',
            5 => 'Loved it',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function improvementTags(): array
    {
        return [
            'all_good' => 'All good',
            'taste' => 'Taste / flavour',
            'freshness' => 'Freshness',
            'packaging' => 'Packaging',
            'delivery' => 'Delivery timing',
            'price' => 'Price',
            'website' => 'Website / checkout',
            'new_blends' => 'New blend ideas',
        ];
    }

    public static function shoppingLabel(int $rating): string
    {
        return self::shoppingRatings()[$rating] ?? (string) $rating;
    }

    /**
     * @param  list<string>  $tags
     * @return list<string>
     */
    public static function improvementLabels(array $tags): array
    {
        $options = self::improvementTags();

        return collect($tags)
            ->map(fn (string $tag): string => $options[$tag] ?? $tag)
            ->values()
            ->all();
    }
}
