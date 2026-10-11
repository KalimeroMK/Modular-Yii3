<?php

declare(strict_types=1);

namespace App\Modules\Product\Domain\Model;

use App\Modules\Category\Domain\Model\Category;
use App\Modules\Tag\Domain\Model\Tag;
use DateTimeImmutable;
use Yiisoft\ActiveRecord\ActiveQueryInterface;
use Yiisoft\ActiveRecord\ActiveRecord;

/**
 * Generated from `modules.yaml`. Edit it freely: the generator never overwrites an existing file without `--force`.
 */
final class Product extends ActiveRecord
{
    public int $id;
    public string $name = '';
    public string $slug = '';
    public string $price = '';
    public bool $is_active = false;
    public string $status = 'draft';
    public ?array $metadata = null;
    public ?string $description = null;
    public ?DateTimeImmutable $published_at = null;
    public int $category_id = 0;
    public ?DateTimeImmutable $created_at = null;
    public ?DateTimeImmutable $updated_at = null;

    public function tableName(): string
    {
        return '{{%products}}';
    }

    public function relationQuery(string $name): ActiveQueryInterface
    {
        return match ($name) {
            'category' => $this->hasOne(Category::class, ['id' => 'category_id']),
            'tags' => $this->hasMany(Tag::class, ['id' => 'tag_id'])
                ->viaTable('{{%product_tag}}', ['product_id' => 'id']),
            default => parent::relationQuery($name),
        };
    }
}
