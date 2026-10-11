<?php

declare(strict_types=1);

namespace App\Tests\Modules\Category;

use App\Tests\Support\ModuleTestCase;
use App\Modules\Category\Application\Dto\CategoryInput;
use App\Modules\Category\Application\Dto\CategoryResource;
use App\Modules\Category\Application\Service\CategoryService;
use App\Modules\Category\Domain\Exception\CategoryNotFoundException;
use App\Modules\Category\Infrastructure\Migration\M261011122949CreateCategoriesTable;
use App\Modules\Category\Infrastructure\Persistence\ActiveRecordCategoryRepository;

final class CategoryServiceTest extends ModuleTestCase
{
    private CategoryService $service;

    public function testCreateStoresTheInput(): void
    {
        $category = $this->service->create($this->makeInput());

        self::assertGreaterThan(0, $category->id);
        self::assertSame('sample-name', $category->name);
        self::assertSame('sample-slug', $category->slug);
    }

    public function testGetReturnsTheStoredRecord(): void
    {
        $created = $this->service->create($this->makeInput());

        self::assertSame($created->id, $this->service->get($created->id)->id);
    }

    public function testGetThrowsWhenMissing(): void
    {
        $this->expectException(CategoryNotFoundException::class);

        $this->service->get(404);
    }

    public function testUpdateChangesStoredValues(): void
    {
        $created = $this->service->create($this->makeInput());

        $updated = $this->service->update($created->id, $this->makeUpdatedInput());

        self::assertSame($created->id, $updated->id);
        self::assertSame('updated-name', $updated->name);
        self::assertSame('updated-slug', $updated->slug);
    }

    public function testDeleteRemovesTheRecord(): void
    {
        $created = $this->service->create($this->makeInput());

        $this->service->delete($created->id);

        self::assertSame(0, $this->service->count());
    }

    public function testResourceExposesTheIdentifier(): void
    {
        $created = $this->service->create($this->makeInput());

        self::assertSame($created->id, CategoryResource::toArray($created)['id']);
    }

    protected function migrations(): array
    {
        return [new M261011122949CreateCategoriesTable()];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new CategoryService(new ActiveRecordCategoryRepository());
    }

    private function makeInput(): CategoryInput
    {
        $input = new CategoryInput();
        $input->name = 'sample-name';
        $input->slug = 'sample-slug';

        return $input;
    }

    private function makeUpdatedInput(): CategoryInput
    {
        $input = new CategoryInput();
        $input->name = 'updated-name';
        $input->slug = 'updated-slug';

        return $input;
    }
}
