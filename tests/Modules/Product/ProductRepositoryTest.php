<?php

declare(strict_types=1);

namespace App\Tests\Modules\Product;

use App\Modules\Product\Domain\Model\Product;
use App\Modules\Product\Domain\Repository\ProductRepositoryInterface;
use App\Modules\Product\Infrastructure\Migration\M261011122949CreateProductsTable;
use App\Modules\Product\Infrastructure\Persistence\ActiveRecordProductRepository;
use App\Tests\Support\ModuleTestCase;
use DateTimeImmutable;

final class ProductRepositoryTest extends ModuleTestCase
{
    private ProductRepositoryInterface $repository;

    public function testSavePersistsEveryColumn(): void
    {
        $product = $this->makeProduct();

        $this->repository->save($product);

        self::assertGreaterThan(0, $product->id);

        $loaded = $this->repository->findById($product->id);

        self::assertNotNull($loaded);
        self::assertSame('sample-name', $loaded->name);
        self::assertSame('sample-slug', $loaded->slug);
        self::assertSame('9.99', $loaded->price);
        self::assertSame(true, $loaded->is_active);
        self::assertSame('draft', $loaded->status);
        self::assertSame(['key' => 'value'], $loaded->metadata);
        self::assertSame('Some text', $loaded->description);
        self::assertSame('2026-01-01 10:00:00', $loaded->published_at?->format('Y-m-d H:i:s'));
        self::assertSame(1, $loaded->category_id);
    }

    public function testFindAllReturnsSavedRecords(): void
    {
        $this->repository->save($this->makeProduct('-1'));
        $this->repository->save($this->makeProduct('-2'));

        self::assertCount(2, $this->repository->findAll());
        self::assertSame(2, $this->repository->count());
    }

    public function testFindAllAppliesLimitAndOffset(): void
    {
        $this->repository->save($this->makeProduct('-1'));
        $this->repository->save($this->makeProduct('-2'));

        self::assertCount(1, $this->repository->findAll(1));
        self::assertCount(1, $this->repository->findAll(10, 1));
    }

    public function testDeleteRemovesTheRecord(): void
    {
        $product = $this->repository->save($this->makeProduct());

        $this->repository->delete($product);

        self::assertNull($this->repository->findById($product->id));
        self::assertSame(0, $this->repository->count());
    }

    public function testFindByIdReturnsNullForUnknownId(): void
    {
        self::assertNull($this->repository->findById(404));
    }

    protected function migrations(): array
    {
        return [new M261011122949CreateProductsTable()];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new ActiveRecordProductRepository();
    }

    private function makeProduct(string $suffix = ''): Product
    {
        $product = new Product();
        $product->name = 'sample-name';
        $product->slug = 'sample-slug' . $suffix;
        $product->price = '9.99';
        $product->is_active = true;
        $product->status = 'draft';
        $product->metadata = ['key' => 'value'];
        $product->description = 'Some text';
        $product->published_at = new DateTimeImmutable('2026-01-01 10:00:00');
        $product->category_id = 1;

        return $product;
    }
}
