<?php

declare(strict_types=1);

namespace App\Tests\Core\Module;

use App\Core\Module\Definition\FieldType;
use App\Core\Module\Definition\RelationType;
use App\Core\Module\YamlModuleParser;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class YamlModuleParserTest extends TestCase
{
    public function testShortFormFields(): void
    {
        $module = $this->parse([
            'Product' => [
                'fields' => [
                    'name' => 'string:120',
                    'price' => 'decimal:10,2',
                    'description' => 'text?',
                    'status' => 'enum:draft,published',
                ],
            ],
        ]);

        [$name, $price, $description, $status] = $module->fields;

        self::assertSame(FieldType::String, $name->type);
        self::assertSame(120, $name->size);
        self::assertFalse($name->nullable);

        self::assertSame(FieldType::Decimal, $price->type);
        self::assertSame(10, $price->size);
        self::assertSame(2, $price->scale);

        self::assertTrue($description->nullable);

        self::assertSame(['draft', 'published'], $status->enumValues);
    }

    public function testExpandedFormFields(): void
    {
        $module = $this->parse([
            'Product' => [
                'fields' => [
                    'slug' => ['type' => 'string', 'size' => 150, 'unique' => true, 'nullable' => true],
                ],
            ],
        ]);

        $slug = $module->fields[0];

        self::assertTrue($slug->unique);
        self::assertTrue($slug->nullable);
        self::assertSame(150, $slug->size);
    }

    public function testRelationKeysFollowTheOwningSide(): void
    {
        $module = $this->parse([
            'Product' => [
                'fields' => ['name' => 'string'],
                'relations' => [
                    'category' => 'belongsTo:Category',
                    'comments' => 'hasMany:Comment',
                    'tags' => 'belongsToMany:Tag',
                ],
            ],
        ]);

        [$category, $comments, $tags] = $module->relations;

        self::assertSame(RelationType::BelongsTo, $category->type);
        self::assertSame('category_id', $category->foreignKey);
        self::assertTrue($category->ownsColumn());

        self::assertSame('product_id', $comments->foreignKey);
        self::assertFalse($comments->ownsColumn());

        self::assertSame('product_tag', $tags->junctionTable);
    }

    public function testBelongsToAddsAColumnToTheOwnTable(): void
    {
        $module = $this->parse([
            'Product' => [
                'fields' => ['name' => 'string'],
                'relations' => ['category' => 'belongsTo:Category'],
            ],
        ]);

        $columnNames = array_map(static fn($field): string => $field->name, $module->writableFields());

        self::assertSame(['name', 'category_id'], $columnNames);
    }

    public function testTableNameDefaultsToThePluralisedModuleName(): void
    {
        self::assertSame('categories', $this->parse(['Category' => ['fields' => ['name' => 'string']]])->table);
    }

    public function testPolymorphicRelationsAreRejectedWithAnExplicitMessage(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/no polymorphic relations/');

        $this->parse([
            'Comment' => [
                'fields' => ['body' => 'text'],
                'relations' => ['commentable' => 'morphTo:Product'],
            ],
        ]);
    }

    public function testUnknownFieldTypeIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/Unknown field type "wat"/');

        $this->parse(['Product' => ['fields' => ['name' => 'wat']]]);
    }

    public function testModuleWithoutFieldsOrRelationsIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->parse(['Product' => []]);
    }

    public function testEmptyDocumentIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new YamlModuleParser())->parse([]);
    }

    /**
     * @param array<string, mixed> $modules
     */
    private function parse(array $modules): \App\Core\Module\Definition\ModuleDefinition
    {
        return (new YamlModuleParser())->parse(['modules' => $modules])[0];
    }
}
