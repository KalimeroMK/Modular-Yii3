<?php

declare(strict_types=1);

namespace App\Tests\Modules\Category;

use App\Modules\Category\Domain\Model\Category;
use App\Modules\Category\Domain\Repository\CategoryRepositoryInterface;
use App\Modules\Category\Infrastructure\Migration\M261011122949CreateCategoriesTable;
use App\Modules\Category\Infrastructure\Persistence\ActiveRecordCategoryRepository;
use App\Tests\Support\ModuleTestCase;

final class CategoryRepositoryTest extends ModuleTestCase
{
    private CategoryRepositoryInterface $repository;

    public function testSavePersistsEveryColumn(): void
    {
        $category = $this->makeCategory();

        $this->repository->save($category);

        self::assertGreaterThan(0, $category->id);

        $loaded = $this->repository->findById($category->id);

        self::assertNotNull($loaded);
        self::assertSame('sample-name', $loaded->name);
        self::assertSame('sample-slug', $loaded->slug);
    }

    public function testFindAllReturnsSavedRecords(): void
    {
        $this->repository->save($this->makeCategory('-1'));
        $this->repository->save($this->makeCategory('-2'));

        self::assertCount(2, $this->repository->findAll());
        self::assertSame(2, $this->repository->count());
    }

    public function testFindAllAppliesLimitAndOffset(): void
    {
        $this->repository->save($this->makeCategory('-1'));
        $this->repository->save($this->makeCategory('-2'));

        self::assertCount(1, $this->repository->findAll(1));
        self::assertCount(1, $this->repository->findAll(10, 1));
    }

    public function testDeleteRemovesTheRecord(): void
    {
        $category = $this->repository->save($this->makeCategory());

        $this->repository->delete($category);

        self::assertNull($this->repository->findById($category->id));
        self::assertSame(0, $this->repository->count());
    }

    public function testFindByIdReturnsNullForUnknownId(): void
    {
        self::assertNull($this->repository->findById(404));
    }

    protected function migrations(): array
    {
        return [new M261011122949CreateCategoriesTable()];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new ActiveRecordCategoryRepository();
    }

    private function makeCategory(string $suffix = ''): Category
    {
        $category = new Category();
        $category->name = 'sample-name';
        $category->slug = 'sample-slug' . $suffix;

        return $category;
    }
}
