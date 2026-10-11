<?php

declare(strict_types=1);

namespace App\Core\Module\Definition;

use InvalidArgumentException;

use function array_column;
use function implode;
use function in_array;
use function sprintf;

/**
 * Relation kinds the generator can express with `yiisoft/active-record`.
 *
 * `morphMany` and `morphOne` are emulated with an ON condition on the relation query, which Active Record
 * applies to lazy loading, eager loading and joins alike. The inverse direction (`morphTo`) cannot be a
 * relation at all — the target class differs per row — so it is resolved by a generated resolver service.
 */
enum RelationType: string
{
    case BelongsTo = 'belongsTo';
    case HasOne = 'hasOne';
    case HasMany = 'hasMany';
    case BelongsToMany = 'belongsToMany';
    case MorphMany = 'morphMany';
    case MorphOne = 'morphOne';

    private const INVERSE_MORPH = ['morphTo', 'morphToMany'];

    public static function fromName(string $name): self
    {
        if (in_array($name, self::INVERSE_MORPH, true)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Relation type "%s" cannot be a relation in Active Record, because the target class differs '
                    . 'per row. Declare `morphMany`/`morphOne` on the owning modules instead; the generator then '
                    . 'writes a resolver service on this module to walk the relation back.',
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

    public function isMorph(): bool
    {
        return $this === self::MorphMany || $this === self::MorphOne;
    }
}
