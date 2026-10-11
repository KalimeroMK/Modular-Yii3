<?php

declare(strict_types=1);

namespace App\Modules\Comment\Application\Service;

use App\Modules\Category\Domain\Model\Category;
use App\Modules\Comment\Domain\Model\Comment;
use App\Modules\Product\Domain\Model\Product;
use Yiisoft\ActiveRecord\ActiveRecordInterface;

use function array_keys;

/**
 * Walks a Comment back to the record that owns it.
 *
 * Active Record cannot express the inverse of a polymorphic relation, because the target class differs per
 * row, so the mapping lives here rather than on the model. Add an entry when a new module starts owning
 * Comment records.
 */
final class CommentableResolver
{
    /**
     * @var array<string, class-string<ActiveRecordInterface>>
     */
    private const MAP = [
        'category' => Category::class,
        'product' => Product::class,
    ];

    public function resolve(Comment $model): ?ActiveRecordInterface
    {
        $class = self::MAP[$model->commentable_type] ?? null;

        if ($class === null) {
            return null;
        }

        $record = $class::query()->findByPk($model->commentable_id);

        return $record instanceof ActiveRecordInterface ? $record : null;
    }

    /**
     * The values accepted in the `commentable_type` column.
     *
     * @return list<string>
     */
    public static function types(): array
    {
        return array_keys(self::MAP);
    }
}
