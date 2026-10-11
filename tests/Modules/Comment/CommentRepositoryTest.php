<?php

declare(strict_types=1);

namespace App\Tests\Modules\Comment;

use App\Modules\Comment\Domain\Model\Comment;
use App\Modules\Comment\Domain\Repository\CommentRepositoryInterface;
use App\Modules\Comment\Infrastructure\Migration\M261011122949CreateCommentsTable;
use App\Modules\Comment\Infrastructure\Persistence\ActiveRecordCommentRepository;
use App\Tests\Support\ModuleTestCase;

final class CommentRepositoryTest extends ModuleTestCase
{
    private CommentRepositoryInterface $repository;

    public function testSavePersistsEveryColumn(): void
    {
        $comment = $this->makeComment();

        $this->repository->save($comment);

        self::assertGreaterThan(0, $comment->id);

        $loaded = $this->repository->findById($comment->id);

        self::assertNotNull($loaded);
        self::assertSame('sample-author', $loaded->author);
        self::assertSame('Some text', $loaded->body);
        self::assertSame('sample-commentable-type', $loaded->commentable_type);
        self::assertSame(1, $loaded->commentable_id);
    }

    public function testFindAllReturnsSavedRecords(): void
    {
        $this->repository->save($this->makeComment('-1'));
        $this->repository->save($this->makeComment('-2'));

        self::assertCount(2, $this->repository->findAll());
        self::assertSame(2, $this->repository->count());
    }

    public function testFindAllAppliesLimitAndOffset(): void
    {
        $this->repository->save($this->makeComment('-1'));
        $this->repository->save($this->makeComment('-2'));

        self::assertCount(1, $this->repository->findAll(1));
        self::assertCount(1, $this->repository->findAll(10, 1));
    }

    public function testDeleteRemovesTheRecord(): void
    {
        $comment = $this->repository->save($this->makeComment());

        $this->repository->delete($comment);

        self::assertNull($this->repository->findById($comment->id));
        self::assertSame(0, $this->repository->count());
    }

    public function testFindByIdReturnsNullForUnknownId(): void
    {
        self::assertNull($this->repository->findById(404));
    }

    protected function migrations(): array
    {
        return [new M261011122949CreateCommentsTable()];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new ActiveRecordCommentRepository();
    }

    private function makeComment(string $suffix = ''): Comment
    {
        $comment = new Comment();
        $comment->author = 'sample-author';
        $comment->body = 'Some text';
        $comment->commentable_type = 'sample-commentable-type';
        $comment->commentable_id = 1;

        return $comment;
    }
}
