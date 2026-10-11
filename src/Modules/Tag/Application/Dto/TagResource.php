<?php

declare(strict_types=1);

namespace App\Modules\Tag\Application\Dto;

use App\Modules\Tag\Domain\Model\Tag;

use function array_map;

/**
 * Shapes a Tag for the API response.
 */
final class TagResource
{
    /**
     * @return array<string, mixed>
     */
    public static function toArray(Tag $model): array
    {
        return [
            'id' => $model->id,
            'name' => $model->name,
            'color' => $model->color,
            'created_at' => $model->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $model->updated_at?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * @param list<Tag> $models
     *
     * @return list<array<string, mixed>>
     */
    public static function toArrayList(array $models): array
    {
        return array_map(self::toArray(...), $models);
    }
}
