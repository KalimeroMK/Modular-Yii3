<?php

declare(strict_types=1);

namespace App\Modules\Comment\Domain\Repository;

use App\Modules\Comment\Domain\Model\Comment;

interface CommentRepositoryInterface
{
    /**
     * @return list<Comment>
     */
    public function findAll(int $limit = 50, int $offset = 0): array;

    public function findById(int $id): ?Comment;

    public function save(Comment $comment): Comment;

    public function delete(Comment $comment): void;

    public function count(): int;
}
