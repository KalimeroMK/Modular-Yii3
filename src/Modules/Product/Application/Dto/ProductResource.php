<?php

declare(strict_types=1);

namespace App\Modules\Product\Application\Dto;

use App\Modules\Product\Domain\Model\Product;

use function array_map;

/**
 * Shapes a Product for the API response.
 */
final class ProductResource
{
    /**
     * @return array<string, mixed>
     */
    public static function toArray(Product $model): array
    {
        return [
            'id' => $model->id,
            'name' => $model->name,
            'slug' => $model->slug,
            'price' => $model->price,
            'is_active' => $model->is_active,
            'status' => $model->status,
            'metadata' => $model->metadata,
            'description' => $model->description,
            'published_at' => $model->published_at?->format('Y-m-d H:i:s'),
            'category_id' => $model->category_id,
            'created_at' => $model->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $model->updated_at?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * @param list<Product> $models
     *
     * @return list<array<string, mixed>>
     */
    public static function toArrayList(array $models): array
    {
        return array_map(self::toArray(...), $models);
    }
}
