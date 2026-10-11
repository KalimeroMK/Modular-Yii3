<?php

declare(strict_types=1);

namespace App\Modules\Tag\Domain\Model;

use App\Modules\Product\Domain\Model\Product;
use DateTimeImmutable;
use Yiisoft\ActiveRecord\ActiveQueryInterface;
use Yiisoft\ActiveRecord\ActiveRecord;

/**
 * Generated from `modules.yaml`. Edit it freely: the generator never overwrites an existing file without `--force`.
 */
final class Tag extends ActiveRecord
{
    public int $id;
    public string $name = '';
    public string $color = '';
    public ?DateTimeImmutable $created_at = null;
    public ?DateTimeImmutable $updated_at = null;

    public function tableName(): string
    {
        return '{{%tags}}';
    }

    public function relationQuery(string $name): ActiveQueryInterface
    {
        return match ($name) {
            'products' => $this->hasMany(Product::class, ['id' => 'product_id'])
                ->viaTable('{{%product_tag}}', ['tag_id' => 'id']),
            default => parent::relationQuery($name),
        };
    }
}
