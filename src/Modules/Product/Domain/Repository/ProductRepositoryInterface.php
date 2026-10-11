<?php

declare(strict_types=1);

namespace App\Modules\Product\Domain\Repository;

use App\Modules\Product\Domain\Model\Product;

interface ProductRepositoryInterface
{
    /**
     * @return list<Product>
     */
    public function findAll(int $limit = 50, int $offset = 0): array;

    public function findById(int $id): ?Product;

    public function save(Product $product): Product;

    public function delete(Product $product): void;

    public function count(): int;
}
