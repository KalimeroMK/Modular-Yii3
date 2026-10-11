<?php

declare(strict_types=1);

use App\Modules\Category\Domain\Repository\CategoryRepositoryInterface;
use App\Modules\Category\Infrastructure\Persistence\ActiveRecordCategoryRepository;

return [
    CategoryRepositoryInterface::class => ActiveRecordCategoryRepository::class,
];
