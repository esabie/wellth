<?php

namespace App\Models;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'slug',
    'description',
    'image_path',
    'is_active',
    'sort_order',
])]
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory;

    public const DRINKS_SLUG = 'cold-pressed-juices';

    /**
     * Slugs that identify the drinks / juices category for minimum-order rules.
     *
     * @var list<string>
     */
    public const DRINKS_SLUGS = [
        self::DRINKS_SLUG,
        'drinks',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function isDrinksCategory(): bool
    {
        if (in_array($this->slug, self::DRINKS_SLUGS, true)) {
            return true;
        }

        // Production currently has name "Drinks" with a mangled slug ("drinksdrinks").
        return strcasecmp((string) $this->name, 'Drinks') === 0
            || str_starts_with($this->slug, 'drinks');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    #[Scope]
    protected function ordered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
