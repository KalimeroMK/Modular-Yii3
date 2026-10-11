<?php

declare(strict_types=1);

namespace App\Modules\Category\Domain\Model;

use App\Modules\Comment\Domain\Model\Comment;
use App\Modules\Product\Domain\Model\Product;
use DateTimeImmutable;
use Yiisoft\ActiveRecord\ActiveQueryInterface;
use Yiisoft\ActiveRecord\ActiveRecord;

/**
 * Generated from `modules.yaml`. Edit it freely: the generator never overwrites an existing file without `--force`.
 */
final class Category extends ActiveRecord
{
    public int $id;
    public string $name = '';
    public string $slug = '';
    public ?DateTimeImmutable $created_at = null;
    public ?DateTimeImmutable $updated_at = null;

    public function tableName(): string
    {
        return '{{%categories}}';
    }

    public function relationQuery(string $name): ActiveQueryInterface
    {
        return match ($name) {
            'products' => $this->hasMany(Product::class, ['category_id' => 'id']),
            'comments' => $this->hasMany(Comment::class, ['commentable_id' => 'id'])
                ->on(['commentable_type' => 'category']),
            default => parent::relationQuery($name),
        };
    }
}
