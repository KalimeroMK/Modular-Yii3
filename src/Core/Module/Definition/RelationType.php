<?php

declare(strict_types=1);

namespace App\Core\Module\Definition;

use InvalidArgumentException;

use function sprintf;

/**
 * Relation kinds supported by `yiisoft/active-record`.
 *
 * Polymorphic relations (`morphTo`, `morphMany`) have no equivalent in Yii Active Record and are rejected
 * by the parser with an explicit message rather than generated as something that only looks like them.
 */
enum RelationType: string
{
    case BelongsTo = 'belongsTo';
    case HasOne = 'hasOne';
    case HasMany = 'hasMany';
    case BelongsToMany = 'belongsToMany';

    private const UNSUPPORTED = ['morphTo', 'morphMany', 'morphOne', 'morphToMany', 'hasManyThrough'];

    public static function fromName(string $name): self
    {
        if (in_array($name, self::UNSUPPORTED, true)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Relation type "%s" is not supported: yiisoft/active-record has no polymorphic relations. '
                    . 'Use an explicit foreign key with belongsTo/hasMany instead.',
                    $name,
                ),
            );
        }

        return self::tryFrom($name) ?? throw new InvalidArgumentException(
            sprintf(
                'Unknown relation type "%s". Supported types: %s.',
                $name,
                implode(', ', array_column(self::cases(), 'value')),
            ),
        );
    }
}
