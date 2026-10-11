<?php

declare(strict_types=1);

namespace App\Modules\Comment\Application\Service;

use App\Modules\Comment\Application\Dto\CommentInput;
use App\Modules\Comment\Domain\Exception\CommentNotFoundException;
use App\Modules\Comment\Domain\Model\Comment;
use App\Modules\Comment\Domain\Repository\CommentRepositoryInterface;

final readonly class CommentService
{
    public function __construct(private CommentRepositoryInterface $repository) {}

    /**
     * @return list<Comment>
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
     * @throws CommentNotFoundException
     */
    public function get(int $id): Comment
    {
        return $this->repository->findById($id) ?? throw CommentNotFoundException::withId($id);
    }

    public function create(CommentInput $input): Comment
    {
        return $this->repository->save($this->fill(new Comment(), $input));
    }

    /**
     * @throws CommentNotFoundException
     */
    public function update(int $id, CommentInput $input): Comment
    {
        return $this->repository->save($this->fill($this->get($id), $input));
    }

    /**
     * @throws CommentNotFoundException
     */
    public function delete(int $id): void
    {
        $this->repository->delete($this->get($id));
    }

    private function fill(Comment $comment, CommentInput $input): Comment
    {
        $comment->author = $input->author;
        $comment->body = $input->body;
        $comment->commentable_type = $input->commentable_type;
        $comment->commentable_id = $input->commentable_id;

        return $comment;
    }
}
