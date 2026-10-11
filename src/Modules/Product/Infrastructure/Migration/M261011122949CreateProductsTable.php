<?php

declare(strict_types=1);

namespace App\Modules\Product\Infrastructure\Migration;

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;
use Yiisoft\Db\Migration\TransactionalMigrationInterface;

final class M261011122949CreateProductsTable implements RevertibleMigrationInterface, TransactionalMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $cb = $b->columnBuilder();

        $b->createTable('products', [
            'id' => $cb::primaryKey(),
            'name' => $cb::string(120)->notNull(),
            'slug' => $cb::string(150)->notNull(),
            'price' => $cb::decimal(10, 2)->notNull(),
            'is_active' => $cb::boolean()->notNull(),
            'status' => $cb::string(32)->notNull(),
            'metadata' => $cb::json(),
            'description' => $cb::text(),
            'published_at' => $cb::datetime(),
            'category_id' => $cb::integer()->notNull(),
            'created_at' => $cb::datetime(),
            'updated_at' => $cb::datetime(),
        ]);

        $b->createIndex('products', 'idx_products_slug_unique', 'slug', 'UNIQUE');
        $b->createIndex('products', 'idx_products_category_id', 'category_id');

        // SQLite cannot add constraints to an existing table, and the test suite runs on SQLite.
        if ($b->getDb()->getDriverName() !== 'sqlite') {
            $b->addForeignKey(
                'products',
                'fk_products_category_id',
                'category_id',
                'categories',
                'id',
                'CASCADE',
            );
        }

        // The junction table may already be created by the Tag module.
        if ($b->getDb()->getSchema()->getTableSchema('product_tag', true) === null) {
            $b->createTable('product_tag', [
                'product_id' => $cb::integer()->notNull(),
                'tag_id' => $cb::integer()->notNull(),
            ]);
            $b->createIndex('product_tag', 'idx_product_tag_unique', ['product_id', 'tag_id'], 'UNIQUE');
        }
    }

    public function down(MigrationBuilder $b): void
    {
        if ($b->getDb()->getSchema()->getTableSchema('product_tag', true) !== null) {
            $b->dropTable('product_tag');
        }

        $b->dropTable('products');
    }
}
