<?php

declare(strict_types=1);

namespace App\Modules\Product\Infrastructure\Persistence;

use App\Modules\Product\Domain\Model\Product;
use App\Modules\Product\Domain\Repository\ProductRepositoryInterface;
use DateTimeImmutable;

final class ActiveRecordProductRepository implements ProductRepositoryInterface
{
    public function findAll(int $limit = 50, int $offset = 0): array
    {
        /** @var list<Product> */
        return Product::query()
            ->orderBy(['id' => SORT_DESC])
            ->limit($limit)
            ->offset($offset)
            ->all();
    }

    public function findById(int $id): ?Product
    {
        /** @var Product|null */
        return Product::query()
            ->andWhere(['id' => $id])
            ->one();
    }

    public function save(Product $product): Product
    {
        $now = new DateTimeImmutable();

        if ($product->isNew()) {
            $product->created_at = $now;
        }

        $product->updated_at = $now;

        $product->save();

        return $product;
    }

    public function delete(Product $product): void
    {
        $product->delete();
    }

    public function count(): int
    {
        return (int) Product::query()
            ->count();
    }
}
