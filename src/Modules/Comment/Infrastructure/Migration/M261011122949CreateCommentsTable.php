<?php

declare(strict_types=1);

namespace App\Modules\Comment\Infrastructure\Migration;

use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;
use Yiisoft\Db\Migration\TransactionalMigrationInterface;

final class M261011122949CreateCommentsTable implements RevertibleMigrationInterface, TransactionalMigrationInterface
{
    public function up(MigrationBuilder $b): void
    {
        $cb = $b->columnBuilder();

        $b->createTable('comments', [
            'id' => $cb::primaryKey(),
            'author' => $cb::string(120)->notNull(),
            'body' => $cb::text()->notNull(),
            'commentable_type' => $cb::string(32)->notNull(),
            'commentable_id' => $cb::integer()->notNull(),
            'created_at' => $cb::datetime(),
            'updated_at' => $cb::datetime(),
        ]);

        $b->createIndex('comments', 'idx_comments_commentable', ['commentable_type', 'commentable_id']);
    }

    public function down(MigrationBuilder $b): void
    {
        $b->dropTable('comments');
    }
}
