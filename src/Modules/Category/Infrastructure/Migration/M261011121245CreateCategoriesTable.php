<?php

declare(strict_types=1);

namespace App\Modules\Category\Infrastructure\Migration;

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;
use Yiisoft\Db\Migration\TransactionalMigrationInterface;

final class M261011121245CreateCategoriesTable implements RevertibleMigrationInterface, TransactionalMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $cb = $b->columnBuilder();

        $b->createTable('categories', [
            'id' => $cb::primaryKey(),
            'name' => $cb::string(120)->notNull(),
            'slug' => $cb::string(150)->notNull(),
            'created_at' => $cb::datetime(),
            'updated_at' => $cb::datetime(),
        ]);

        $b->createIndex('categories', 'idx_categories_slug_unique', 'slug', 'UNIQUE');
    }

    public function down(MigrationBuilder $b): void
    {
        $b->dropTable('categories');
    }
}
