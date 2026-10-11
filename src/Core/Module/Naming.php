<?php

declare(strict_types=1);

namespace App\Core\Module;

use Yiisoft\Strings\Inflector;

use function preg_match;
use function strtolower;
use function substr;

/**
 * Name conversions used by the module generator.
 *
 * The framework inflector has no pluralizer, so a small English one lives here.
 */
final class Naming
{
    private const IRREGULAR = [
        'person' => 'people',
        'man' => 'men',
        'woman' => 'women',
        'child' => 'children',
        'tooth' => 'teeth',
        'foot' => 'feet',
        'mouse' => 'mice',
        'goose' => 'geese',
    ];

    private const UNCOUNTABLE = ['equipment', 'information', 'money', 'news', 'data', 'media'];

    private static ?Inflector $inflector = null;

    public static function pascal(string $value): string
    {
        return self::inflector()->toPascalCase($value);
    }

    public static function camel(string $value): string
    {
        return self::inflector()->toCamelCase($value);
    }

    public static function snake(string $value): string
    {
        return self::inflector()->toSnakeCase($value);
    }

    public static function kebab(string $value): string
    {
        return self::inflector()->toSnakeCase($value, false) === ''
            ? ''
            : str_replace('_', '-', self::inflector()->toSnakeCase($value));
    }

    public static function pluralize(string $value): string
    {
        $lower = strtolower($value);

        if (in_array($lower, self::UNCOUNTABLE, true)) {
            return $value;
        }

        return self::IRREGULAR[$lower] ?? match (true) {
            (bool) preg_match('/(s|x|z|ch|sh)$/i', $value) => $value . 'es',
            (bool) preg_match('/[^aeiou]y$/i', $value) => substr($value, 0, -1) . 'ies',
            (bool) preg_match('/(f|fe)$/i', $value) => (string) preg_replace('/(f|fe)$/i', 'ves', $value),
            default => $value . 's',
        };
    }

    /**
     * `Product` -> `products`, `CategoryItem` -> `category_items`.
     */
    public static function tableName(string $moduleName): string
    {
        $snake = self::snake($moduleName);
        $parts = explode('_', $snake);
        $last = array_pop($parts);
        $parts[] = self::pluralize($last);

        return implode('_', $parts);
    }

    /**
     * Junction table for a many-to-many relation, alphabetically ordered: `category_product`.
     */
    public static function junctionTable(string $left, string $right): string
    {
        $names = [self::snake($left), self::snake($right)];
        sort($names);

        return implode('_', $names);
    }

    /**
     * `Product` -> `product_id`.
     */
    public static function foreignKey(string $moduleName): string
    {
        return self::snake($moduleName) . '_id';
    }

    /**
     * `Product` -> `products`, used as the URL segment.
     */
    public static function routeSegment(string $moduleName): string
    {
        return str_replace('_', '-', self::tableName($moduleName));
    }

    private static function inflector(): Inflector
    {
        return self::$inflector ??= new Inflector();
    }
}
