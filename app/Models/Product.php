<?php

namespace App\Models;

use App\Support\FormulaCatalog;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'category_id',
    'name',
    'slug',
    'sku',
    'short_description',
    'bottle_size',
    'description',
    'taste',
    'ritual',
    'highlights',
    'ingredients',
    'price',
    'compare_at_price',
    'image_path',
    'stock_quantity',
    'is_active',
    'sort_order',
])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'compare_at_price' => 'decimal:2',
            'stock_quantity' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'highlights' => 'array',
            'ingredients' => 'array',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function hasStoryContent(): bool
    {
        return filled($this->taste)
            || filled($this->ritual)
            || filled($this->bottle_size)
            || $this->highlightList() !== []
            || $this->ingredientList() !== [];
    }

    /**
     * @return list<string>
     */
    public function highlightList(): array
    {
        return collect($this->highlights ?? [])
            ->map(fn (mixed $highlight): string => trim((string) $highlight))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return list<array{name: string, image: string|null}>
     */
    public function storefrontIngredients(): array
    {
        return collect($this->ingredientList())
            ->map(fn (array $ingredient): array => [
                'name' => $ingredient['name'],
                'image' => $this->publicAssetUrl($ingredient['image'] ?? null),
            ])
            ->all();
    }

    /**
     * Public image URL for storefront rendering.
     *
     * Prefers an uploaded storage path, then falls back to the formula catalog.
     */
    public function imageUrl(?FormulaCatalog $formulas = null): ?string
    {
        if (filled($this->image_path)) {
            return $this->publicAssetUrl($this->image_path);
        }

        $formulas ??= app(FormulaCatalog::class);

        return $formulas->find($this->slug)['image'] ?? null;
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

    /**
     * Products that can show a storefront image (upload or formula catalog).
     *
     * @param  list<string>  $formulaSlugs
     */
    #[Scope]
    protected function withStorefrontImage(Builder $query, array $formulaSlugs = []): Builder
    {
        return $query->where(function (Builder $imageQuery) use ($formulaSlugs): void {
            $imageQuery->whereNotNull('image_path')->where('image_path', '!=', '');

            if ($formulaSlugs !== []) {
                $imageQuery->orWhereIn('slug', $formulaSlugs);
            }
        });
    }

    /**
     * @return list<array{name: string, image: string|null}>
     */
    private function ingredientList(): array
    {
        return collect($this->ingredients ?? [])
            ->map(function (mixed $ingredient): ?array {
                if (! is_array($ingredient)) {
                    return null;
                }

                $name = trim((string) ($ingredient['name'] ?? ''));

                if ($name === '') {
                    return null;
                }

                $image = $ingredient['image'] ?? null;

                return [
                    'name' => $name,
                    'image' => filled($image) ? (string) $image : null,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function publicAssetUrl(?string $path): ?string
    {
        if (! filled($path)) {
            return null;
        }

        if (
            str_starts_with($path, '/')
            || str_starts_with($path, 'http://')
            || str_starts_with($path, 'https://')
        ) {
            return $path;
        }

        return '/storage/'.$path;
    }
}
