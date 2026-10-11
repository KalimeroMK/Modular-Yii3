<?php

declare(strict_types=1);

namespace App\Tests\Modules\Tag;

use App\Tests\Support\ModuleTestCase;
use App\Modules\Tag\Application\Dto\TagInput;
use App\Modules\Tag\Application\Dto\TagResource;
use App\Modules\Tag\Application\Service\TagService;
use App\Modules\Tag\Domain\Exception\TagNotFoundException;
use App\Modules\Tag\Infrastructure\Migration\M261011122949CreateTagsTable;
use App\Modules\Tag\Infrastructure\Persistence\ActiveRecordTagRepository;

final class TagServiceTest extends ModuleTestCase
{
    private TagService $service;

    public function testCreateStoresTheInput(): void
    {
        $tag = $this->service->create($this->makeInput());

        self::assertGreaterThan(0, $tag->id);
        self::assertSame('sample-name', $tag->name);
        self::assertSame('sample-color', $tag->color);
    }

    public function testGetReturnsTheStoredRecord(): void
    {
        $created = $this->service->create($this->makeInput());

        self::assertSame($created->id, $this->service->get($created->id)->id);
    }

    public function testGetThrowsWhenMissing(): void
    {
        $this->expectException(TagNotFoundException::class);

        $this->service->get(404);
    }

    public function testUpdateChangesStoredValues(): void
    {
        $created = $this->service->create($this->makeInput());

        $updated = $this->service->update($created->id, $this->makeUpdatedInput());

        self::assertSame($created->id, $updated->id);
        self::assertSame('updated-name', $updated->name);
        self::assertSame('updated-color', $updated->color);
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

        self::assertSame($created->id, TagResource::toArray($created)['id']);
    }

    protected function migrations(): array
    {
        return [new M261011122949CreateTagsTable()];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new TagService(new ActiveRecordTagRepository());
    }

    private function makeInput(): TagInput
    {
        $input = new TagInput();
        $input->name = 'sample-name';
        $input->color = 'sample-color';

        return $input;
    }

    private function makeUpdatedInput(): TagInput
    {
        $input = new TagInput();
        $input->name = 'updated-name';
        $input->color = 'updated-color';

        return $input;
    }
}
