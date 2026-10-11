<?php

declare(strict_types=1);

namespace App\Core\Module\Definition;

use App\Core\Module\Naming;

use function array_filter;
use function array_values;

/**
 * Everything the generator needs to know about one module.
 */
final readonly class ModuleDefinition
{
    /**
     * @param list<Field> $fields
     * @param list<Relation> $relations
     */
    public function __construct(
        public string $name,
        public string $table,
        public array $fields,
        public array $relations = [],
        public bool $api = true,
        public bool $tests = true,
        public bool $timestamps = true,
        public bool $softDelete = false,
    ) {}

    public function namespace(): string
    {
        return 'App\\Modules\\' . $this->name;
    }

    public function variable(): string
    {
        return Naming::camel($this->name);
    }

    public function routeSegment(): string
    {
        return Naming::routeSegment($this->name);
    }

    /**
     * Fields that are enums and therefore get a generated PHP enum class.
     *
     * @return list<Field>
     */
    public function enumFields(): array
    {
        return array_values(array_filter($this->fields, static fn(Field $field): bool => $field->isEnum()));
    }

    /**
     * Relations whose foreign key is a column of this module's table.
     *
     * @return list<Relation>
     */
    public function owningRelations(): array
    {
        return array_values(array_filter($this->relations, static fn(Relation $r): bool => $r->ownsColumn()));
    }

    /**
     * @return list<Relation>
     */
    public function junctionRelations(): array
    {
        return array_values(array_filter(
            $this->relations,
            static fn(Relation $r): bool => $r->type === RelationType::BelongsToMany,
        ));
    }

    /**
     * Columns that are written by the user: own fields plus `belongsTo` foreign keys.
     *
     * @return list<Field>
     */
    public function writableFields(): array
    {
        $fields = $this->fields;

        foreach ($this->owningRelations() as $relation) {
            $fields[] = new Field(
                $relation->foreignKey,
                FieldType::Integer,
                $relation->nullable,
                index: true,
            );
        }

        return $fields;
    }
}
