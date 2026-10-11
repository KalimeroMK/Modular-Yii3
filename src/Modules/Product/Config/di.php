<?php

declare(strict_types=1);

use App\Modules\Product\Domain\Repository\ProductRepositoryInterface;
use App\Modules\Product\Infrastructure\Persistence\ActiveRecordProductRepository;

return [
    ProductRepositoryInterface::class => ActiveRecordProductRepository::class,
];
