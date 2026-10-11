<?php

declare(strict_types=1);

use App\Modules\Tag\Domain\Repository\TagRepositoryInterface;
use App\Modules\Tag\Infrastructure\Persistence\ActiveRecordTagRepository;

return [
    TagRepositoryInterface::class => ActiveRecordTagRepository::class,
];
