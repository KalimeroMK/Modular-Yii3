<?php

declare(strict_types=1);

namespace App\Core\Module\Definition;

use App\Core\Module\Naming;
use InvalidArgumentException;

use function explode;
use function sprintf;
use function str_contains;
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
    ) {}

    /**
     * Parses the short form: `user: belongsTo:User`, `categories: belongsToMany:Category`.
     */
    public static function fromShortForm(string $name, string $definition, string $moduleName): self
    {
        $definition = trim($definition);

        if (!str_contains($definition, ':')) {
            throw new InvalidArgumentException(
                sprintf('Relation "%s" must be written as `<type>:<Module>`, for example `belongsTo:User`.', $name),
            );
        }

        $parts = explode(':', $definition);
        $type = RelationType::fromName(trim($parts[0]));
        $target = Naming::pascal(trim($parts[1]));
        $explicitKey = isset($parts[2]) ? trim($parts[2]) : null;

        return self::create($name, $type, $target, $moduleName, $explicitKey);
    }

    /**
     * @param array<array-key, mixed> $definition
     */
    public static function fromArray(string $name, array $definition, string $moduleName): self
    {
        $type = RelationType::fromName((string) ($definition['type'] ?? ''));
        $target = Naming::pascal((string) ($definition['target'] ?? $definition['model'] ?? ''));

        $relation = self::create(
            $name,
            $type,
            $target,
            $moduleName,
            isset($definition['foreignKey']) ? (string) $definition['foreignKey'] : null,
        );

        if (isset($definition['junctionTable'])) {
            return new self(
                $relation->name,
                $relation->type,
                $relation->target,
                $relation->foreignKey,
                $relation->localKey,
                (string) $definition['junctionTable'],
                (bool) ($definition['nullable'] ?? $relation->nullable),
            );
        }

        return new self(
            $relation->name,
            $relation->type,
            $relation->target,
            $relation->foreignKey,
            $relation->localKey,
            $relation->junctionTable,
            (bool) ($definition['nullable'] ?? $relation->nullable),
        );
    }

    private static function create(
        string $name,
        RelationType $type,
        string $target,
        string $moduleName,
        ?string $explicitKey,
    ): self {
        if ($target === '') {
            throw new InvalidArgumentException(sprintf('Relation "%s" has no target module.', $name));
        }

        return match ($type) {
            // The foreign key lives on this module's table.
            RelationType::BelongsTo => new self(
                $name,
                $type,
                $target,
                $explicitKey ?? Naming::foreignKey($target),
            ),
            // The foreign key lives on the target module's table.
            RelationType::HasOne, RelationType::HasMany => new self(
                $name,
                $type,
                $target,
                $explicitKey ?? Naming::foreignKey($moduleName),
            ),
            RelationType::BelongsToMany => new self(
                $name,
                $type,
                $target,
                $explicitKey ?? Naming::foreignKey($target),
                junctionTable: Naming::junctionTable($moduleName, $target),
            ),
        };
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
        return $this->type === RelationType::HasMany || $this->type === RelationType::BelongsToMany;
    }
}
