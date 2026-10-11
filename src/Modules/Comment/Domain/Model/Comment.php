<?php

declare(strict_types=1);

namespace App\Modules\Comment\Domain\Model;

use DateTimeImmutable;
use Yiisoft\ActiveRecord\ActiveQueryInterface;
use Yiisoft\ActiveRecord\ActiveRecord;

/**
 * Generated from `modules.yaml`. Edit it freely: the generator never overwrites an existing file without `--force`.
 */
final class Comment extends ActiveRecord
{
    public int $id;
    public string $author = '';
    public string $body = '';
    public string $commentable_type = '';
    public int $commentable_id = 0;
    public ?DateTimeImmutable $created_at = null;
    public ?DateTimeImmutable $updated_at = null;

    public function tableName(): string
    {
        return '{{%comments}}';
    }

    public function relationQuery(string $name): ActiveQueryInterface
    {
        return match ($name) {
            default => parent::relationQuery($name),
        };
    }
}
