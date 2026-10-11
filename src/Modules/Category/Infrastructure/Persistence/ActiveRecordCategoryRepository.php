<?php

declare(strict_types=1);

namespace App\Modules\Category\Infrastructure\Persistence;

use App\Modules\Category\Domain\Model\Category;
use App\Modules\Category\Domain\Repository\CategoryRepositoryInterface;
use DateTimeImmutable;

final class ActiveRecordCategoryRepository implements CategoryRepositoryInterface
{
    public function findAll(int $limit = 50, int $offset = 0): array
    {
        /** @var list<Category> */
        return Category::query()
            ->orderBy(['id' => SORT_DESC])
            ->limit($limit)
            ->offset($offset)
            ->all();
    }

    public function findById(int $id): ?Category
    {
        /** @var Category|null */
        return Category::query()
            ->andWhere(['id' => $id])
            ->one();
    }

    public function save(Category $category): Category
    {
        $now = new DateTimeImmutable();

        if ($category->isNew()) {
            $category->created_at = $now;
        }

        $category->updated_at = $now;

        $category->save();

        return $category;
    }

    public function delete(Category $category): void
    {
        $category->delete();
    }

    public function count(): int
    {
        return (int) Category::query()
            ->count();
    }
}
