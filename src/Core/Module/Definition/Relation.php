<?php

declare(strict_types=1);

namespace App\Core\Module\Definition;

use App\Core\Module\Naming;
use InvalidArgumentException;

use function count;
use function explode;
use function sprintf;
use function trim;

/**
 * A relation between two modules.
 */
final readonly class Relation
{
    public function __construct(
        public string $name,
        public RelationType $type,
        public string $target,
        public string $foreignKey,
        public string $localKey = 'id',
        public ?string $junctionTable = null,
        public bool $nullable = false,
        public ?string $morphName = null,
    ) {}

    /**
     * Parses the short form: `user: belongsTo:User`, `comments: morphMany:Comment:commentable`.
     */
    public static function fromShortForm(string $name, string $definition, string $moduleName): self
    {
        $parts = explode(':', trim($definition));

        if (count($parts) < 2) {
            throw new InvalidArgumentException(
                sprintf('Relation "%s" must be written as `<type>:<Module>`, for example `belongsTo:User`.', $name),
            );
        }

        return self::create(
            $name,
            RelationType::fromName(trim($parts[0])),
            Naming::pascal(trim($parts[1])),
            $moduleName,
            isset($parts[2]) ? trim($parts[2]) : null,
        );
    }

    /**
     * @param array<array-key, mixed> $definition
     */
    public static function fromArray(string $name, array $definition, string $moduleName): self
    {
        $type = RelationType::fromName((string) ($definition['type'] ?? ''));
        $qualifier = match (true) {
            isset($definition['morphName']) => (string) $definition['morphName'],
            isset($definition['foreignKey']) => (string) $definition['foreignKey'],
            default => null,
        };

        $relation = self::create(
            $name,
            $type,
            Naming::pascal((string) ($definition['target'] ?? $definition['model'] ?? '')),
            $moduleName,
            $qualifier,
        );

        return new self(
            $relation->name,
            $relation->type,
            $relation->target,
            $relation->foreignKey,
            $relation->localKey,
            isset($definition['junctionTable']) ? (string) $definition['junctionTable'] : $relation->junctionTable,
            (bool) ($definition['nullable'] ?? $relation->nullable),
            $relation->morphName,
        );
    }

    /**
     * The column on the target table that stores which module owns the row.
     */
    public function morphTypeColumn(): string
    {
        return (string) $this->morphName . '_type';
    }

    /**
     * True when the relation adds a column to the module's own table.
     */
    public function ownsColumn(): bool
    {
        return $this->type === RelationType::BelongsTo;
    }

    public function isCollection(): bool
    {
        return $this->type === RelationType::HasMany
            || $this->type === RelationType::BelongsToMany
            || $this->type === RelationType::MorphMany;
    }

    /**
     * @param string|null $qualifier The foreign key for ordinary relations, the morph name for polymorphic ones.
     */
    private static function create(
        string $name,
        RelationType $type,
        string $target,
        string $moduleName,
        ?string $qualifier,
    ): self {
        if ($target === '') {
            throw new InvalidArgumentException(sprintf('Relation "%s" has no target module.', $name));
        }

        if ($type->isMorph()) {
            if ($qualifier === null || $qualifier === '') {
                throw new InvalidArgumentException(sprintf(
                    'Relation "%s" needs a morph name: `%s:%s:commentable` creates `commentable_type` and '
                    . '`commentable_id` on %s.',
                    $name,
                    $type->value,
                    $target,
                    $target,
                ));
            }

            $morphName = Naming::snake($qualifier);

            return new self($name, $type, $target, $morphName . '_id', morphName: $morphName);
        }

        return match ($type) {
            // The foreign key lives on this module's table.
            RelationType::BelongsTo => new self(
                $name,
                $type,
                $target,
                $qualifier ?? Naming::foreignKey($target),
            ),
            // The foreign key lives on the target module's table.
            RelationType::HasOne, RelationType::HasMany => new self(
                $name,
                $type,
                $target,
                $qualifier ?? Naming::foreignKey($moduleName),
            ),
            RelationType::BelongsToMany => new self(
                $name,
                $type,
                $target,
                $qualifier ?? Naming::foreignKey($target),
                junctionTable: Naming::junctionTable($moduleName, $target),
            ),
            default => throw new InvalidArgumentException(
                sprintf('Relation type "%s" is not handled by the generator.', $type->value),
            ),
        };
    }
}
