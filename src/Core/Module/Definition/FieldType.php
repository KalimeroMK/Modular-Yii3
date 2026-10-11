<?php

declare(strict_types=1);

namespace App\Core\Module\Definition;

use InvalidArgumentException;

use function sprintf;
use function strtolower;

/**
 * Field types accepted in `modules.yaml`, mapped to a PHP type, a column builder call and validation rules.
 */
enum FieldType: string
{
    case String = 'string';
    case Text = 'text';
    case Integer = 'integer';
    case BigInteger = 'bigint';
    case Float = 'float';
    case Decimal = 'decimal';
    case Boolean = 'boolean';
    case Json = 'json';
    case Date = 'date';
    case DateTime = 'datetime';
    case Timestamp = 'timestamp';
    case Time = 'time';
    case Uuid = 'uuid';
    case Enum = 'enum';

    private const ALIASES = [
        'int' => 'integer',
        'biginteger' => 'bigint',
        'bool' => 'boolean',
        'double' => 'float',
        'str' => 'string',
        'varchar' => 'string',
        'longtext' => 'text',
        'array' => 'json',
        'object' => 'json',
    ];

    public static function fromName(string $name): self
    {
        $name = strtolower($name);
        $name = self::ALIASES[$name] ?? $name;

        return self::tryFrom($name) ?? throw new InvalidArgumentException(
            sprintf(
                'Unknown field type "%s". Supported types: %s.',
                $name,
                implode(', ', array_column(self::cases(), 'value')),
            ),
        );
    }

    public function phpType(): string
    {
        return match ($this) {
            self::Integer, self::BigInteger => 'int',
            self::Float => 'float',
            // DECIMAL is read back as a string so precision is never lost in a float.
            self::Decimal => 'string',
            self::Boolean => 'bool',
            self::Json => 'array',
            self::Date, self::DateTime, self::Timestamp, self::Time => 'DateTimeImmutable',
            default => 'string',
        };
    }
}
