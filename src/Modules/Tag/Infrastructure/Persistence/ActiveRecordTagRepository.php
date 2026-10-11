<?php

declare(strict_types=1);

namespace App\Modules\Tag\Infrastructure\Persistence;

use App\Modules\Tag\Domain\Model\Tag;
use App\Modules\Tag\Domain\Repository\TagRepositoryInterface;
use DateTimeImmutable;

final class ActiveRecordTagRepository implements TagRepositoryInterface
{
    public function findAll(int $limit = 50, int $offset = 0): array
    {
        /** @var list<Tag> */
        return Tag::query()
            ->orderBy(['id' => SORT_DESC])
            ->limit($limit)
            ->offset($offset)
            ->all();
    }

    public function findById(int $id): ?Tag
    {
        /** @var Tag|null */
        return Tag::query()
            ->andWhere(['id' => $id])
            ->one();
    }

    public function save(Tag $tag): Tag
    {
        $now = new DateTimeImmutable();

        if ($tag->isNew()) {
            $tag->created_at = $now;
        }

        $tag->updated_at = $now;

        $tag->save();

        return $tag;
    }

    public function delete(Tag $tag): void
    {
        $tag->delete();
    }

    public function count(): int
    {
        return (int) Tag::query()
            ->count();
    }
}
