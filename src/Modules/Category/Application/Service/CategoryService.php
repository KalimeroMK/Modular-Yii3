<?php

declare(strict_types=1);

namespace App\Modules\Category\Application\Service;

use App\Modules\Category\Application\Dto\CategoryInput;
use App\Modules\Category\Domain\Exception\CategoryNotFoundException;
use App\Modules\Category\Domain\Model\Category;
use App\Modules\Category\Domain\Repository\CategoryRepositoryInterface;

final readonly class CategoryService
{
    public function __construct(private CategoryRepositoryInterface $repository) {}

    /**
     * @return list<Category>
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
     * @throws CategoryNotFoundException
     */
    public function get(int $id): Category
    {
        return $this->repository->findById($id) ?? throw CategoryNotFoundException::withId($id);
    }

    public function create(CategoryInput $input): Category
    {
        return $this->repository->save($this->fill(new Category(), $input));
    }

    /**
     * @throws CategoryNotFoundException
     */
    public function update(int $id, CategoryInput $input): Category
    {
        return $this->repository->save($this->fill($this->get($id), $input));
    }

    /**
     * @throws CategoryNotFoundException
     */
    public function delete(int $id): void
    {
        $this->repository->delete($this->get($id));
    }

    private function fill(Category $category, CategoryInput $input): Category
    {
        $category->name = $input->name;
        $category->slug = $input->slug;

        return $category;
    }
}
