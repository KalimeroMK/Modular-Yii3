<?php

declare(strict_types=1);

namespace App\Tests\Core\Module;

use App\Core\Module\Naming;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class NamingTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function pluralProvider(): iterable
    {
        yield 'regular' => ['product', 'products'];
        yield 'ends with s' => ['status', 'statuses'];
        yield 'ends with ch' => ['batch', 'batches'];
        yield 'consonant + y' => ['category', 'categories'];
        yield 'vowel + y' => ['day', 'days'];
        yield 'ends with f' => ['shelf', 'shelves'];
        yield 'irregular' => ['person', 'people'];
        yield 'uncountable' => ['media', 'media'];
    }

    #[DataProvider('pluralProvider')]
    public function testPluralize(string $singular, string $plural): void
    {
        self::assertSame($plural, Naming::pluralize($singular));
    }

    public function testTableNamePluralisesOnlyTheLastWord(): void
    {
        self::assertSame('products', Naming::tableName('Product'));
        self::assertSame('category_items', Naming::tableName('CategoryItem'));
        self::assertSame('order_statuses', Naming::tableName('OrderStatus'));
    }

    public function testJunctionTableIsOrderedSoBothSidesAgree(): void
    {
        self::assertSame('category_product', Naming::junctionTable('Product', 'Category'));
        self::assertSame('category_product', Naming::junctionTable('Category', 'Product'));
    }

    public function testForeignKey(): void
    {
        self::assertSame('product_id', Naming::foreignKey('Product'));
        self::assertSame('category_item_id', Naming::foreignKey('CategoryItem'));
    }

    public function testRouteSegment(): void
    {
        self::assertSame('category-items', Naming::routeSegment('CategoryItem'));
    }
}
