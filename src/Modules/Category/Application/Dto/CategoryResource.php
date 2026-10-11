<?php

declare(strict_types=1);

namespace App\Modules\Category\Application\Dto;

use App\Modules\Category\Domain\Model\Category;

use function array_map;

/**
 * Shapes a Category for the API response.
 */
final class CategoryResource
{
    /**
     * @return array<string, mixed>
     */
    public static function toArray(Category $model): array
    {
        return [
            'id' => $model->id,
            'name' => $model->name,
            'slug' => $model->slug,
            'created_at' => $model->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $model->updated_at?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * @param list<Category> $models
     *
     * @return list<array<string, mixed>>
     */
    public static function toArrayList(array $models): array
    {
        return array_map(self::toArray(...), $models);
    }
}
