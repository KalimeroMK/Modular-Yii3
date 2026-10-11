<?php

declare(strict_types=1);

namespace App\Modules\Comment\Infrastructure\Persistence;

use App\Modules\Comment\Domain\Model\Comment;
use App\Modules\Comment\Domain\Repository\CommentRepositoryInterface;
use DateTimeImmutable;

final class ActiveRecordCommentRepository implements CommentRepositoryInterface
{
    public function findAll(int $limit = 50, int $offset = 0): array
    {
        /** @var list<Comment> */
        return Comment::query()
            ->orderBy(['id' => SORT_DESC])
            ->limit($limit)
            ->offset($offset)
            ->all();
    }

    public function findById(int $id): ?Comment
    {
        /** @var Comment|null */
        return Comment::query()
            ->andWhere(['id' => $id])
            ->one();
    }

    public function save(Comment $comment): Comment
    {
        $now = new DateTimeImmutable();

        if ($comment->isNew()) {
            $comment->created_at = $now;
        }

        $comment->updated_at = $now;

        $comment->save();

        return $comment;
    }

    public function delete(Comment $comment): void
    {
        $comment->delete();
    }

    public function count(): int
    {
        return (int) Comment::query()
            ->count();
    }
}
