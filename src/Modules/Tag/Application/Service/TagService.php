<?php

declare(strict_types=1);

namespace App\Modules\Tag\Application\Service;

use App\Modules\Tag\Application\Dto\TagInput;
use App\Modules\Tag\Domain\Exception\TagNotFoundException;
use App\Modules\Tag\Domain\Model\Tag;
use App\Modules\Tag\Domain\Repository\TagRepositoryInterface;

final readonly class TagService
{
    public function __construct(private TagRepositoryInterface $repository) {}

    /**
     * @return list<Tag>
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
     * @throws TagNotFoundException
     */
    public function get(int $id): Tag
    {
        return $this->repository->findById($id) ?? throw TagNotFoundException::withId($id);
    }

    public function create(TagInput $input): Tag
    {
        return $this->repository->save($this->fill(new Tag(), $input));
    }

    /**
     * @throws TagNotFoundException
     */
    public function update(int $id, TagInput $input): Tag
    {
        return $this->repository->save($this->fill($this->get($id), $input));
    }

    /**
     * @throws TagNotFoundException
     */
    public function delete(int $id): void
    {
        $this->repository->delete($this->get($id));
    }

    private function fill(Tag $tag, TagInput $input): Tag
    {
        $tag->name = $input->name;
        $tag->color = $input->color;

        return $tag;
    }
}
