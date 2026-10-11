<?php

declare(strict_types=1);

use App\Modules\Comment\Domain\Repository\CommentRepositoryInterface;
use App\Modules\Comment\Infrastructure\Persistence\ActiveRecordCommentRepository;

return [
    CommentRepositoryInterface::class => ActiveRecordCommentRepository::class,
];
