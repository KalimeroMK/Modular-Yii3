<?php

declare(strict_types=1);

namespace App\Tests\Support;

use PHPUnit\Framework\TestCase;
use Yiisoft\Cache\ArrayCache;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Connection\ConnectionProvider;
use Yiisoft\Db\Cache\SchemaCache;
use Yiisoft\Db\Migration\Informer\NullMigrationInformer;
use Yiisoft\Db\Migration\MigrationBuilder;
use Yiisoft\Db\Migration\MigrationInterface;
use Yiisoft\Db\Sqlite\Connection;
use Yiisoft\Db\Sqlite\Driver;

/**
 * Base class for generated module tests: a throwaway in-memory SQLite database with the module's own migrations
 * applied, and nothing else. No application container, no fixtures to keep in sync.
 */
abstract class ModuleTestCase extends TestCase
{
    protected ConnectionInterface $db;

    /**
     * Migrations to apply before each test.
     *
     * @return list<MigrationInterface>
     */
    abstract protected function migrations(): array;

    protected function setUp(): void
    {
        parent::setUp();

        $this->db = new Connection(new Driver('sqlite::memory:'), new SchemaCache(new ArrayCache()));
        ConnectionProvider::set($this->db);

        $builder = new MigrationBuilder($this->db, new NullMigrationInformer());

        foreach ($this->migrations() as $migration) {
            $migration->up($builder);
        }
    }

    protected function tearDown(): void
    {
        ConnectionProvider::remove();
        $this->db->close();

        parent::tearDown();
    }
}
