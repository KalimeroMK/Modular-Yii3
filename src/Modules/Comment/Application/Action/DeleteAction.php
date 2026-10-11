<?php

declare(strict_types=1);

namespace App\Modules\Comment\Application\Action;

use Psr\Http\Message\ResponseInterface;
use Yiisoft\DataResponse\ResponseFactory\JsonResponseFactory;
use Yiisoft\Http\Status;
use Yiisoft\Router\CurrentRoute;
use App\Modules\Comment\Application\Service\CommentService;
use App\Modules\Comment\Domain\Exception\CommentNotFoundException;

final readonly class DeleteAction
{
    public function __construct(
        private CommentService $service,
        private JsonResponseFactory $responseFactory,
    ) {}

    public function __invoke(CurrentRoute $currentRoute): ResponseInterface
    {
        try {
            $this->service->delete((int) $currentRoute->getArgument('id'));
        } catch (CommentNotFoundException $e) {
            return $this->responseFactory->createResponse(['error' => $e->getMessage()], Status::NOT_FOUND);
        }

        return $this->responseFactory->createResponse(null, Status::NO_CONTENT);
    }
}
