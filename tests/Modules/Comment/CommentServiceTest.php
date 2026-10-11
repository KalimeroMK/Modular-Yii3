<?php

declare(strict_types=1);

namespace App\Tests\Modules\Comment;

use App\Tests\Support\ModuleTestCase;
use App\Modules\Comment\Application\Dto\CommentInput;
use App\Modules\Comment\Application\Dto\CommentResource;
use App\Modules\Comment\Application\Service\CommentService;
use App\Modules\Comment\Domain\Exception\CommentNotFoundException;
use App\Modules\Comment\Infrastructure\Migration\M261011122949CreateCommentsTable;
use App\Modules\Comment\Infrastructure\Persistence\ActiveRecordCommentRepository;

final class CommentServiceTest extends ModuleTestCase
{
    private CommentService $service;

    public function testCreateStoresTheInput(): void
    {
        $comment = $this->service->create($this->makeInput());

        self::assertGreaterThan(0, $comment->id);
        self::assertSame('sample-author', $comment->author);
        self::assertSame('Some text', $comment->body);
        self::assertSame('sample-commentable-type', $comment->commentable_type);
        self::assertSame(1, $comment->commentable_id);
    }

    public function testGetReturnsTheStoredRecord(): void
    {
        $created = $this->service->create($this->makeInput());

        self::assertSame($created->id, $this->service->get($created->id)->id);
    }

    public function testGetThrowsWhenMissing(): void
    {
        $this->expectException(CommentNotFoundException::class);

        $this->service->get(404);
    }

    public function testUpdateChangesStoredValues(): void
    {
        $created = $this->service->create($this->makeInput());

        $updated = $this->service->update($created->id, $this->makeUpdatedInput());

        self::assertSame($created->id, $updated->id);
        self::assertSame('updated-author', $updated->author);
        self::assertSame('Updated text', $updated->body);
        self::assertSame('updated-commentable-type', $updated->commentable_type);
        self::assertSame(2, $updated->commentable_id);
    }

    public function testDeleteRemovesTheRecord(): void
    {
        $created = $this->service->create($this->makeInput());

        $this->service->delete($created->id);

        self::assertSame(0, $this->service->count());
    }

    public function testResourceExposesTheIdentifier(): void
    {
        $created = $this->service->create($this->makeInput());

        self::assertSame($created->id, CommentResource::toArray($created)['id']);
    }

    protected function migrations(): array
    {
        return [new M261011122949CreateCommentsTable()];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new CommentService(new ActiveRecordCommentRepository());
    }

    private function makeInput(): CommentInput
    {
        $input = new CommentInput();
        $input->author = 'sample-author';
        $input->body = 'Some text';
        $input->commentable_type = 'sample-commentable-type';
        $input->commentable_id = 1;

        return $input;
    }

    private function makeUpdatedInput(): CommentInput
    {
        $input = new CommentInput();
        $input->author = 'updated-author';
        $input->body = 'Updated text';
        $input->commentable_type = 'updated-commentable-type';
        $input->commentable_id = 2;

        return $input;
    }
}
