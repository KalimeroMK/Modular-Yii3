<?php

declare(strict_types=1);

namespace App\Modules\Comment\Application\Dto;

use App\Modules\Comment\Domain\Model\Comment;

use function array_map;

/**
 * Shapes a Comment for the API response.
 */
final class CommentResource
{
    /**
     * @return array<string, mixed>
     */
    public static function toArray(Comment $model): array
    {
        return [
            'id' => $model->id,
            'author' => $model->author,
            'body' => $model->body,
            'commentable_type' => $model->commentable_type,
            'commentable_id' => $model->commentable_id,
            'created_at' => $model->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $model->updated_at?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * @param list<Comment> $models
     *
     * @return list<array<string, mixed>>
     */
    public static function toArrayList(array $models): array
    {
        return array_map(self::toArray(...), $models);
    }
}
