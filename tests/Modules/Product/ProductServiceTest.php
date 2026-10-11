<?php

declare(strict_types=1);

namespace App\Tests\Modules\Product;

use App\Tests\Support\ModuleTestCase;
use App\Modules\Product\Application\Dto\ProductInput;
use App\Modules\Product\Application\Dto\ProductResource;
use App\Modules\Product\Application\Service\ProductService;
use App\Modules\Product\Domain\Exception\ProductNotFoundException;
use App\Modules\Product\Infrastructure\Migration\M261011121245CreateProductsTable;
use App\Modules\Product\Infrastructure\Persistence\ActiveRecordProductRepository;

final class ProductServiceTest extends ModuleTestCase
{
    private ProductService $service;

    public function testCreateStoresTheInput(): void
    {
        $product = $this->service->create($this->makeInput());

        self::assertGreaterThan(0, $product->id);
        self::assertSame('sample-name', $product->name);
        self::assertSame('sample-slug', $product->slug);
        self::assertSame('9.99', $product->price);
        self::assertSame(true, $product->is_active);
        self::assertSame('draft', $product->status);
        self::assertSame(['key' => 'value'], $product->metadata);
        self::assertSame('Some text', $product->description);
        self::assertSame('2026-01-01 10:00:00', $product->published_at?->format('Y-m-d H:i:s'));
        self::assertSame(1, $product->category_id);
    }

    public function testGetReturnsTheStoredRecord(): void
    {
        $created = $this->service->create($this->makeInput());

        self::assertSame($created->id, $this->service->get($created->id)->id);
    }

    public function testGetThrowsWhenMissing(): void
    {
        $this->expectException(ProductNotFoundException::class);

        $this->service->get(404);
    }

    public function testUpdateChangesStoredValues(): void
    {
        $created = $this->service->create($this->makeInput());

        $updated = $this->service->update($created->id, $this->makeUpdatedInput());

        self::assertSame($created->id, $updated->id);
        self::assertSame('updated-name', $updated->name);
        self::assertSame('updated-slug', $updated->slug);
        self::assertSame('19.99', $updated->price);
        self::assertSame(false, $updated->is_active);
        self::assertSame('published', $updated->status);
        self::assertSame(['key' => 'updated'], $updated->metadata);
        self::assertSame('Updated text', $updated->description);
        self::assertSame('2026-02-02 10:00:00', $updated->published_at?->format('Y-m-d H:i:s'));
        self::assertSame(2, $updated->category_id);
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

        self::assertSame($created->id, ProductResource::toArray($created)['id']);
    }

    protected function migrations(): array
    {
        return [new M261011121245CreateProductsTable()];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ProductService(new ActiveRecordProductRepository());
    }

    private function makeInput(): ProductInput
    {
        $input = new ProductInput();
        $input->name = 'sample-name';
        $input->slug = 'sample-slug';
        $input->price = '9.99';
        $input->is_active = true;
        $input->status = 'draft';
        $input->metadata = ['key' => 'value'];
        $input->description = 'Some text';
        $input->published_at = '2026-01-01 10:00:00';
        $input->category_id = 1;

        return $input;
    }

    private function makeUpdatedInput(): ProductInput
    {
        $input = new ProductInput();
        $input->name = 'updated-name';
        $input->slug = 'updated-slug';
        $input->price = '19.99';
        $input->is_active = false;
        $input->status = 'published';
        $input->metadata = ['key' => 'updated'];
        $input->description = 'Updated text';
        $input->published_at = '2026-02-02 10:00:00';
        $input->category_id = 2;

        return $input;
    }
}
