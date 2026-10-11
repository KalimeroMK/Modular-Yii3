<?php

declare(strict_types=1);

namespace App\Modules\Category\Domain\Repository;

use App\Modules\Category\Domain\Model\Category;

interface CategoryRepositoryInterface
{
    /**
     * @return list<Category>
     */
    public function findAll(int $limit = 50, int $offset = 0): array;

    public function findById(int $id): ?Category;

    public function save(Category $category): Category;

    public function delete(Category $category): void;

    public function count(): int;
}
