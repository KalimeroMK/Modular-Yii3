<?php

declare(strict_types=1);

namespace App\Tests\Modules\Tag;

use App\Modules\Tag\Domain\Model\Tag;
use App\Modules\Tag\Domain\Repository\TagRepositoryInterface;
use App\Modules\Tag\Infrastructure\Migration\M261011121245CreateTagsTable;
use App\Modules\Tag\Infrastructure\Persistence\ActiveRecordTagRepository;
use App\Tests\Support\ModuleTestCase;

final class TagRepositoryTest extends ModuleTestCase
{
    private TagRepositoryInterface $repository;

    public function testSavePersistsEveryColumn(): void
    {
        $tag = $this->makeTag();

        $this->repository->save($tag);

        self::assertGreaterThan(0, $tag->id);

        $loaded = $this->repository->findById($tag->id);

        self::assertNotNull($loaded);
        self::assertSame('sample-name', $loaded->name);
        self::assertSame('sample-color', $loaded->color);
    }

    public function testFindAllReturnsSavedRecords(): void
    {
        $this->repository->save($this->makeTag('-1'));
        $this->repository->save($this->makeTag('-2'));

        self::assertCount(2, $this->repository->findAll());
        self::assertSame(2, $this->repository->count());
    }

    public function testFindAllAppliesLimitAndOffset(): void
    {
        $this->repository->save($this->makeTag('-1'));
        $this->repository->save($this->makeTag('-2'));

        self::assertCount(1, $this->repository->findAll(1));
        self::assertCount(1, $this->repository->findAll(10, 1));
    }

    public function testDeleteRemovesTheRecord(): void
    {
        $tag = $this->repository->save($this->makeTag());

        $this->repository->delete($tag);

        self::assertNull($this->repository->findById($tag->id));
        self::assertSame(0, $this->repository->count());
    }

    public function testFindByIdReturnsNullForUnknownId(): void
    {
        self::assertNull($this->repository->findById(404));
    }

    protected function migrations(): array
    {
        return [new M261011121245CreateTagsTable()];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new ActiveRecordTagRepository();
    }

    private function makeTag(string $suffix = ''): Tag
    {
        $tag = new Tag();
        $tag->name = 'sample-name';
        $tag->color = 'sample-color';

        return $tag;
    }
}
