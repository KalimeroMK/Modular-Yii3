<?php

declare(strict_types=1);

namespace App\Modules\Tag\Domain\Repository;

use App\Modules\Tag\Domain\Model\Tag;

interface TagRepositoryInterface
{
    /**
     * @return list<Tag>
     */
    public function findAll(int $limit = 50, int $offset = 0): array;

    public function findById(int $id): ?Tag;

    public function save(Tag $tag): Tag;

    public function delete(Tag $tag): void;

    public function count(): int;
}
