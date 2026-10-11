<?php

declare(strict_types=1);

namespace App\Modules\Tag\Infrastructure\Migration;

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;
use Yiisoft\Db\Migration\TransactionalMigrationInterface;

final class M261011122949CreateTagsTable implements RevertibleMigrationInterface, TransactionalMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $cb = $b->columnBuilder();

        $b->createTable('tags', [
            'id' => $cb::primaryKey(),
            'name' => $cb::string(60)->notNull(),
            'color' => $cb::string(7)->notNull(),
            'created_at' => $cb::datetime(),
            'updated_at' => $cb::datetime(),
        ]);

        // The junction table may already be created by the Product module.
        if ($b->getDb()->getSchema()->getTableSchema('product_tag', true) === null) {
            $b->createTable('product_tag', [
                'tag_id' => $cb::integer()->notNull(),
                'product_id' => $cb::integer()->notNull(),
            ]);
            $b->createIndex('product_tag', 'idx_product_tag_unique', ['tag_id', 'product_id'], 'UNIQUE');
        }
    }

    public function down(MigrationBuilder $b): void
    {
        if ($b->getDb()->getSchema()->getTableSchema('product_tag', true) !== null) {
            $b->dropTable('product_tag');
        }

        $b->dropTable('tags');
    }
}
