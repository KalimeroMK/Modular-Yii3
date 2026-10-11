<?php

declare(strict_types=1);

namespace App\Core\Module;

use App\Core\Module\Definition\Field;
use App\Core\Module\Definition\FieldType;
use App\Core\Module\Definition\ModuleDefinition;
use App\Core\Module\Definition\Relation;
use App\Core\Module\Definition\RelationType;

use function array_map;
use function array_unique;
use function array_values;
use function implode;
use function in_array;
use function sort;
use function sprintf;
use function str_contains;
use function substr;
use function var_export;

/**
 * Builds the PHP snippets injected into the stubs.
 *
 * Every method returns code already indented for its place in the stub, so stubs keep placeholders at column 0.
 *
 * Date and time columns are the one place where the model and the request differ: `yiisoft/db` hands back
 * `DateTimeImmutable`, while JSON input carries strings, so the input DTO keeps strings and the service converts.
 */
final readonly class CodeFragments
{
    private const TEMPORAL_TYPES = [FieldType::Date, FieldType::DateTime, FieldType::Timestamp, FieldType::Time];

    public function __construct(private ModuleDefinition $module) {}

    public function modelUses(): string
    {
        $uses = [
            \Yiisoft\ActiveRecord\ActiveQueryInterface::class,
            \Yiisoft\ActiveRecord\ActiveRecord::class,
        ];

        if ($this->hasTemporalColumn($this->allColumns())) {
            $uses[] = 'DateTimeImmutable';
        }

        foreach ($this->module->relations as $relation) {
            $uses[] = sprintf('App\\Modules\\%s\\Domain\\Model\\%s', $relation->target, $relation->target);
        }

        return $this->useStatements($uses);
    }

    public function repositoryUses(): string
    {
        $uses = [
            $this->module->namespace() . '\\Domain\\Model\\' . $this->module->name,
            $this->module->namespace() . '\\Domain\\Repository\\' . $this->module->name . 'RepositoryInterface',
        ];

        if ($this->module->timestamps || $this->module->softDelete) {
            $uses[] = 'DateTimeImmutable';
        }

        return $this->useStatements($uses);
    }

    public function serviceUses(): string
    {
        $name = $this->module->name;
        $namespace = $this->module->namespace();

        $uses = [
            $namespace . '\\Application\\Dto\\' . $name . 'Input',
            $namespace . '\\Domain\\Exception\\' . $name . 'NotFoundException',
            $namespace . '\\Domain\\Model\\' . $name,
            $namespace . '\\Domain\\Repository\\' . $name . 'RepositoryInterface',
        ];

        if ($this->hasTemporalColumn($this->inputColumns())) {
            $uses[] = 'DateTimeImmutable';
        }

        return $this->useStatements($uses);
    }

    public function repositoryTestUses(string $migrationClass): string
    {
        $name = $this->module->name;
        $namespace = $this->module->namespace();

        $uses = [
            \App\Tests\Support\ModuleTestCase::class,
            $namespace . '\\Domain\\Model\\' . $name,
            $namespace . '\\Domain\\Repository\\' . $name . 'RepositoryInterface',
            $namespace . '\\Infrastructure\\Migration\\' . $migrationClass,
            $namespace . '\\Infrastructure\\Persistence\\ActiveRecord' . $name . 'Repository',
        ];

        if ($this->hasTemporalColumn($this->inputColumns())) {
            $uses[] = 'DateTimeImmutable';
        }

        return $this->useStatements($uses);
    }

    public function modelProperties(): string
    {
        $lines = ['    public int $id;'];

        foreach ($this->allColumns() as $field) {
            // A non-nullable DateTimeImmutable cannot have a property default, so it stays uninitialised.
            $lines[] = $this->isTemporal($field) && !$field->nullable
                ? sprintf('    public DateTimeImmutable $%s;', $field->name)
                : sprintf(
                    '    public %s $%s = %s;',
                    $field->phpType(),
                    $field->name,
                    $this->defaultLiteral($field),
                );
        }

        return implode("\n", $lines);
    }

    public function relationArms(): string
    {
        if ($this->module->relations === []) {
            return '';
        }

        $lines = [];

        foreach ($this->module->relations as $relation) {
            $lines[] = $this->relationArm($relation);
        }

        return implode("\n", $lines) . "\n";
    }

    public function migrationColumns(): string
    {
        $lines = ["            'id' => \$cb::primaryKey(),"];

        foreach ($this->allColumns() as $field) {
            $lines[] = sprintf("            '%s' => %s,", $field->name, $this->columnExpression($field));
        }

        return implode("\n", $lines);
    }

    public function migrationIndexes(): string
    {
        $lines = [];

        foreach ($this->allColumns() as $field) {
            if ($field->unique) {
                $lines[] = sprintf(
                    "        \$b->createIndex('%s', 'idx_%s_%s_unique', '%s', 'UNIQUE');",
                    $this->module->table,
                    $this->module->table,
                    $field->name,
                    $field->name,
                );
            } elseif ($field->index) {
                $lines[] = sprintf(
                    "        \$b->createIndex('%s', 'idx_%s_%s', '%s');",
                    $this->module->table,
                    $this->module->table,
                    $field->name,
                    $field->name,
                );
            }
        }

        return $lines === [] ? '' : "\n" . implode("\n", $lines) . "\n";
    }

    public function migrationForeignKeys(): string
    {
        $relations = $this->module->owningRelations();

        if ($relations === []) {
            return '';
        }

        $lines = [
            '',
            '        // SQLite cannot add constraints to an existing table, and the test suite runs on SQLite.',
            "        if (\$b->getDb()->getDriverName() !== 'sqlite') {",
        ];

        foreach ($relations as $relation) {
            $lines[] = sprintf(
                "            \$b->addForeignKey(\n"
                . "                '%s',\n"
                . "                'fk_%s_%s',\n"
                . "                '%s',\n"
                . "                '%s',\n"
                . "                '%s',\n"
                . "                'CASCADE',\n"
                . "            );",
                $this->module->table,
                $this->module->table,
                $relation->foreignKey,
                $relation->foreignKey,
                Naming::tableName($relation->target),
                $relation->localKey,
            );
        }

        $lines[] = '        }';
        $lines[] = '';

        return implode("\n", $lines);
    }

    public function migrationJunctionTables(): string
    {
        $relations = $this->module->junctionRelations();

        if ($relations === []) {
            return '';
        }

        $lines = [''];
        $ownKey = Naming::foreignKey($this->module->name);

        foreach ($relations as $relation) {
            $table = (string) $relation->junctionTable;
            $lines[] = sprintf(
                "        // The junction table may already be created by the %s module.\n"
                . "        if (\$b->getDb()->getSchema()->getTableSchema('%s', true) === null) {\n"
                . "            \$b->createTable('%s', [\n"
                . "                '%s' => \$cb::integer()->notNull(),\n"
                . "                '%s' => \$cb::integer()->notNull(),\n"
                . "            ]);\n"
                . "            \$b->createIndex('%s', 'idx_%s_unique', ['%s', '%s'], 'UNIQUE');\n"
                . "        }",
                $relation->target,
                $table,
                $table,
                $ownKey,
                $relation->foreignKey,
                $table,
                $table,
                $ownKey,
                $relation->foreignKey,
            );
        }

        $lines[] = '';

        return implode("\n", $lines);
    }

    public function migrationJunctionDrops(): string
    {
        $relations = $this->module->junctionRelations();

        if ($relations === []) {
            return '';
        }

        $lines = [];

        foreach ($relations as $relation) {
            $table = (string) $relation->junctionTable;
            $lines[] = sprintf(
                "        if (\$b->getDb()->getSchema()->getTableSchema('%s', true) !== null) {\n"
                . "            \$b->dropTable('%s');\n"
                . "        }",
                $table,
                $table,
            );
        }

        return implode("\n", $lines) . "\n\n";
    }

    public function inputUses(): string
    {
        $uses = [
            \Yiisoft\Input\Http\AbstractInput::class,
            \Yiisoft\Input\Http\Attribute\Data\FromBody::class,
        ];

        foreach ($this->inputColumns() as $field) {
            foreach ($this->validationRules($field) as $rule) {
                $uses[] = 'Yiisoft\\Validator\\Rule\\' . $rule['class'];
            }
        }

        return $this->useStatements($uses);
    }

    public function inputProperties(): string
    {
        $blocks = [];

        foreach ($this->inputColumns() as $field) {
            $lines = [];

            foreach ($this->validationRules($field) as $rule) {
                $lines[] = sprintf('    #[%s%s]', $rule['class'], $rule['arguments']);
            }

            $lines[] = sprintf(
                '    public %s $%s = %s;',
                $this->inputPropertyType($field),
                $field->name,
                $this->inputDefaultLiteral($field),
            );

            $blocks[] = implode("\n", $lines);
        }

        return implode("\n\n", $blocks);
    }

    public function assignments(): string
    {
        $lines = [];
        $variable = $this->module->variable();

        foreach ($this->inputColumns() as $field) {
            if (!$this->isTemporal($field)) {
                $lines[] = sprintf('        $%s->%s = $input->%s;', $variable, $field->name, $field->name);
                continue;
            }

            $lines[] = $field->nullable
                ? sprintf(
                    '        $%s->%s = $input->%s === null ? null : new DateTimeImmutable($input->%s);',
                    $variable,
                    $field->name,
                    $field->name,
                    $field->name,
                )
                : sprintf(
                    '        $%s->%s = new DateTimeImmutable($input->%s);',
                    $variable,
                    $field->name,
                    $field->name,
                );
        }

        return implode("\n", $lines);
    }

    public function resourceFields(): string
    {
        $lines = ["            'id' => \$model->id,"];

        foreach ($this->allColumns() as $field) {
            $lines[] = sprintf("            '%s' => %s,", $field->name, $this->readExpression($field, 'model'));
        }

        return implode("\n", $lines);
    }

    /**
     * Property assignments used by the generated tests.
     *
     * @param bool $asModel True for the Active Record model (typed values), false for the input DTO (strings).
     */
    public function testAssignments(
        string $variable,
        bool $updated = false,
        bool $asModel = true,
        ?string $suffixVariable = null,
    ): string {
        $lines = [];

        foreach ($this->inputColumns() as $field) {
            $value = $this->sampleValue($field, $updated, $asModel);

            // Unique columns need a different value per record, otherwise the second insert hits the index.
            if ($suffixVariable !== null && $field->unique && $field->type->phpType() === 'string') {
                $value .= sprintf(' . $%s', $suffixVariable);
            }

            $lines[] = sprintf('        $%s->%s = %s;', $variable, $field->name, $value);
        }

        return implode("\n", $lines);
    }

    /**
     * One assertion per column, so a broken mapping fails loudly instead of silently dropping a field.
     */
    public function testAssertions(string $variable, bool $updated = false): string
    {
        $lines = [];

        foreach ($this->inputColumns() as $field) {
            $lines[] = sprintf(
                '        self::assertSame(%s, %s);',
                $this->sampleValue($field, $updated, false),
                $this->readExpression($field, $variable),
            );
        }

        return implode("\n", $lines);
    }

    public function enumCases(Field $field): string
    {
        $lines = [];

        foreach ($field->enumValues as $value) {
            $lines[] = sprintf('    case %s = %s;', Naming::pascal($value), var_export($value, true));
        }

        return implode("\n", $lines);
    }

    /**
     * Columns stored in the table: declared fields, `belongsTo` keys, timestamps and the soft delete marker.
     *
     * @return list<Field>
     */
    public function allColumns(): array
    {
        $fields = $this->module->writableFields();

        if ($this->module->timestamps) {
            $fields[] = new Field('created_at', FieldType::DateTime, true);
            $fields[] = new Field('updated_at', FieldType::DateTime, true);
        }

        if ($this->module->softDelete) {
            $fields[] = new Field('deleted_at', FieldType::DateTime, true);
        }

        return $fields;
    }

    /**
     * Columns the client may send: everything except timestamps and the soft delete marker.
     *
     * @return list<Field>
     */
    public function inputColumns(): array
    {
        return $this->module->writableFields();
    }

    public function sampleValue(Field $field, bool $updated = false, bool $asModel = true): string
    {
        if ($field->isEnum()) {
            return var_export($field->enumValues[$updated ? 1 : 0] ?? $field->enumValues[0], true);
        }

        if ($this->isTemporal($field)) {
            $literal = match ($field->type) {
                FieldType::Date => $updated ? "'2026-02-02'" : "'2026-01-01'",
                FieldType::Time => $updated ? "'11:30:00'" : "'10:00:00'",
                default => $updated ? "'2026-02-02 10:00:00'" : "'2026-01-01 10:00:00'",
            };

            return $asModel ? sprintf('new DateTimeImmutable(%s)', $literal) : $literal;
        }

        return match ($field->type) {
            FieldType::Integer, FieldType::BigInteger => $updated ? '2' : '1',
            FieldType::Float => $updated ? '19.99' : '9.99',
            FieldType::Decimal => $updated ? "'19.99'" : "'9.99'",
            FieldType::Boolean => $updated ? 'false' : 'true',
            FieldType::Json => $updated ? "['key' => 'updated']" : "['key' => 'value']",
            FieldType::Uuid => $updated
                ? "'5c9e1b2a-0000-4000-8000-000000000002'"
                : "'5c9e1b2a-0000-4000-8000-000000000001'",
            FieldType::Text => $updated ? "'Updated text'" : "'Some text'",
            default => $updated
                ? sprintf("'updated-%s'", Naming::kebab($field->name))
                : sprintf("'sample-%s'", Naming::kebab($field->name)),
        };
    }

    /**
     * How a column is read back as a plain value: date and time columns are formatted, everything else is read as is.
     */
    private function readExpression(Field $field, string $variable): string
    {
        if (!$this->isTemporal($field)) {
            return sprintf('$%s->%s', $variable, $field->name);
        }

        return sprintf(
            '$%s->%s%s->format(%s)',
            $variable,
            $field->name,
            $field->nullable ? '?' : '',
            var_export($this->temporalFormat($field), true),
        );
    }

    private function temporalFormat(Field $field): string
    {
        return match ($field->type) {
            FieldType::Date => 'Y-m-d',
            FieldType::Time => 'H:i:s',
            default => 'Y-m-d H:i:s',
        };
    }

    private function relationArm(Relation $relation): string
    {
        return match ($relation->type) {
            RelationType::BelongsTo => sprintf(
                "            '%s' => \$this->hasOne(%s::class, ['%s' => '%s']),",
                $relation->name,
                $relation->target,
                $relation->localKey,
                $relation->foreignKey,
            ),
            RelationType::HasOne => sprintf(
                "            '%s' => \$this->hasOne(%s::class, ['%s' => '%s']),",
                $relation->name,
                $relation->target,
                $relation->foreignKey,
                $relation->localKey,
            ),
            RelationType::HasMany => sprintf(
                "            '%s' => \$this->hasMany(%s::class, ['%s' => '%s']),",
                $relation->name,
                $relation->target,
                $relation->foreignKey,
                $relation->localKey,
            ),
            RelationType::BelongsToMany => sprintf(
                "            '%s' => \$this->hasMany(%s::class, ['id' => '%s'])\n"
                . "                ->viaTable('{{%%%s}}', ['%s' => 'id']),",
                $relation->name,
                $relation->target,
                $relation->foreignKey,
                (string) $relation->junctionTable,
                Naming::foreignKey($this->module->name),
            ),
        };
    }

    private function columnExpression(Field $field): string
    {
        $expression = match ($field->type) {
            FieldType::String => sprintf('$cb::string(%d)', $field->size ?? 255),
            FieldType::Text => '$cb::text()',
            FieldType::Integer => '$cb::integer()',
            FieldType::BigInteger => '$cb::bigint()',
            FieldType::Float => '$cb::float()',
            FieldType::Decimal => sprintf('$cb::decimal(%d, %d)', $field->size ?? 10, $field->scale ?? 2),
            FieldType::Boolean => '$cb::boolean()',
            FieldType::Json => '$cb::json()',
            FieldType::Date => '$cb::date()',
            FieldType::DateTime => '$cb::datetime()',
            FieldType::Timestamp => '$cb::timestamp()',
            FieldType::Time => '$cb::time()',
            FieldType::Uuid => '$cb::uuid()',
            FieldType::Enum => sprintf('$cb::string(%d)', $field->size ?? 32),
        };

        if (!$field->nullable) {
            $expression .= '->notNull()';
        }

        if ($field->default !== null) {
            $expression .= sprintf('->defaultValue(%s)', var_export($field->default, true));
        }

        return $expression;
    }

    /**
     * @return list<array{class: string, arguments: string}>
     */
    private function validationRules(Field $field): array
    {
        $rules = [];

        if (!$field->nullable && $field->type !== FieldType::Boolean) {
            $rules[] = ['class' => 'Required', 'arguments' => ''];
        }

        if ($field->isEnum()) {
            $values = implode(', ', array_map(static fn(string $v): string => var_export($v, true), $field->enumValues));
            $rule = ['class' => 'In', 'arguments' => sprintf('([%s])', $values)];
            $rules[] = $field->nullable ? $this->skipOnEmpty($rule) : $rule;

            return $rules;
        }

        // A JSON column accepts any shape; validate its contents in the service when the shape is known.
        if ($field->type === FieldType::Json) {
            return $rules;
        }

        $rule = match ($field->type) {
            FieldType::String => str_contains($field->name, 'email')
                ? ['class' => 'Email', 'arguments' => '']
                : ['class' => 'Length', 'arguments' => sprintf('(max: %d)', $field->size ?? 255)],
            FieldType::Text => ['class' => 'Length', 'arguments' => '(max: 65535)'],
            FieldType::Integer, FieldType::BigInteger => ['class' => 'Integer', 'arguments' => ''],
            FieldType::Float, FieldType::Decimal => ['class' => 'Number', 'arguments' => ''],
            FieldType::Boolean => ['class' => 'BooleanValue', 'arguments' => ''],
            default => ['class' => 'Length', 'arguments' => '(max: 64)'],
        };

        $rules[] = $field->nullable ? $this->skipOnEmpty($rule) : $rule;

        return $rules;
    }

    /**
     * A nullable column must not be rejected for being null, so its rule skips empty values.
     *
     * @param array{class: string, arguments: string} $rule
     *
     * @return array{class: string, arguments: string}
     */
    private function skipOnEmpty(array $rule): array
    {
        $rule['arguments'] = $rule['arguments'] === ''
            ? '(skipOnEmpty: true)'
            : substr($rule['arguments'], 0, -1) . ', skipOnEmpty: true)';

        return $rule;
    }

    private function inputPropertyType(Field $field): string
    {
        if ($this->isTemporal($field)) {
            return ($field->nullable ? '?' : '') . 'string';
        }

        return $field->phpType();
    }

    private function inputDefaultLiteral(Field $field): string
    {
        return $this->isTemporal($field) && !$field->nullable ? "''" : $this->defaultLiteral($field);
    }

    private function defaultLiteral(Field $field): string
    {
        if ($field->nullable) {
            return 'null';
        }

        if ($field->default !== null) {
            return var_export($field->default, true);
        }

        if ($field->isEnum()) {
            return var_export($field->enumValues[0], true);
        }

        return match ($field->type->phpType()) {
            'int' => '0',
            'float' => '0.0',
            'bool' => 'false',
            'array' => '[]',
            default => "''",
        };
    }

    private function isTemporal(Field $field): bool
    {
        return in_array($field->type, self::TEMPORAL_TYPES, true);
    }

    /**
     * @param list<Field> $fields
     */
    private function hasTemporalColumn(array $fields): bool
    {
        foreach ($fields as $field) {
            if ($this->isTemporal($field)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<string> $uses
     */
    private function useStatements(array $uses): string
    {
        $uses = array_values(array_unique($uses));
        sort($uses);

        return implode("\n", array_map(static fn(string $use): string => 'use ' . $use . ';', $uses));
    }
}
