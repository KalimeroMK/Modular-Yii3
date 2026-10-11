<?php

declare(strict_types=1);

namespace App\Modules\Product\Application\Service;

use App\Modules\Product\Application\Dto\ProductInput;
use App\Modules\Product\Domain\Exception\ProductNotFoundException;
use App\Modules\Product\Domain\Model\Product;
use App\Modules\Product\Domain\Repository\ProductRepositoryInterface;
use DateTimeImmutable;

final readonly class ProductService
{
    public function __construct(private ProductRepositoryInterface $repository) {}

    /**
     * @return list<Product>
     */
    public function list(int $limit = 50, int $offset = 0): array
    {
        return $this->repository->findAll($limit, $offset);
    }

    public function count(): int
    {
        return $this->repository->count();
    }

    /**
     * @throws ProductNotFoundException
     */
    public function get(int $id): Product
    {
        return $this->repository->findById($id) ?? throw ProductNotFoundException::withId($id);
    }

    public function create(ProductInput $input): Product
    {
        return $this->repository->save($this->fill(new Product(), $input));
    }

    /**
     * @throws ProductNotFoundException
     */
    public function update(int $id, ProductInput $input): Product
    {
        return $this->repository->save($this->fill($this->get($id), $input));
    }

    /**
     * @throws ProductNotFoundException
     */
    public function delete(int $id): void
    {
        $this->repository->delete($this->get($id));
    }

    private function fill(Product $product, ProductInput $input): Product
    {
        $product->name = $input->name;
        $product->slug = $input->slug;
        $product->price = $input->price;
        $product->is_active = $input->is_active;
        $product->status = $input->status;
        $product->metadata = $input->metadata;
        $product->description = $input->description;
        $product->published_at = $input->published_at === null ? null : new DateTimeImmutable($input->published_at);
        $product->category_id = $input->category_id;

        return $product;
    }
}
