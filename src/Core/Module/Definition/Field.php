<?php

declare(strict_types=1);

namespace App\Core\Module\Definition;

use App\Core\Module\Naming;
use InvalidArgumentException;

use function array_map;
use function explode;
use function sprintf;
use function count;
use function str_ends_with;
use function substr;
use function trim;

/**
 * A single column of a module: how it is stored, typed and validated.
 */
final readonly class Field
{
    /**
     * @param list<string> $enumValues
     */
    public function __construct(
        public string $name,
        public FieldType $type,
        public bool $nullable = false,
        public ?int $size = null,
        public ?int $scale = null,
        public array $enumValues = [],
        public bool $unique = false,
        public bool $index = false,
        public mixed $default = null,
    ) {
        if ($type === FieldType::Enum && $enumValues === []) {
            throw new InvalidArgumentException(
                sprintf('Field "%s" is an enum and needs values, for example `%s: enum:draft,published`.', $name, $name),
            );
        }
    }

    /**
     * Parses the short form: `string`, `string:120`, `decimal:10,2`, `enum:draft,published`, `text?`.
     */
    public static function fromShortForm(string $name, string $definition): self
    {
        $definition = trim($definition);
        $nullable = str_ends_with($definition, '?');

        if ($nullable) {
            $definition = substr($definition, 0, -1);
        }

        $arguments = [];

        $parts = explode(':', $definition, 2);

        if (count($parts) === 2) {
            $definition = $parts[0];
            $arguments = array_map(trim(...), explode(',', $parts[1]));
        }

        $type = FieldType::fromName($definition);

        if ($type === FieldType::Enum) {
            return new self($name, $type, $nullable, enumValues: $arguments);
        }

        return new self(
            $name,
            $type,
            $nullable,
            size: isset($arguments[0]) && $arguments[0] !== '' ? (int) $arguments[0] : null,
            scale: isset($arguments[1]) && $arguments[1] !== '' ? (int) $arguments[1] : null,
        );
    }

    /**
     * Parses the expanded form: `{ type: string, size: 120, nullable: true, unique: true }`.
     *
     * @param array<array-key, mixed> $definition
     */
    public static function fromArray(string $name, array $definition): self
    {
        $type = FieldType::fromName((string) ($definition['type'] ?? 'string'));

        /** @var list<string> $values */
        $values = array_map(strval(...), (array) ($definition['values'] ?? []));

        return new self(
            $name,
            $type,
            (bool) ($definition['nullable'] ?? false),
            isset($definition['size']) ? (int) $definition['size'] : null,
            isset($definition['scale']) ? (int) $definition['scale'] : null,
            $values,
            (bool) ($definition['unique'] ?? false),
            (bool) ($definition['index'] ?? false),
            $definition['default'] ?? null,
        );
    }

    public function phpType(): string
    {
        return ($this->nullable ? '?' : '') . $this->type->phpType();
    }

    /**
     * The name of the generated PHP enum, if the field is an enum.
     */
    public function enumClass(string $moduleName): string
    {
        return $moduleName . Naming::pascal($this->name);
    }

    public function isEnum(): bool
    {
        return $this->type === FieldType::Enum;
    }
}
