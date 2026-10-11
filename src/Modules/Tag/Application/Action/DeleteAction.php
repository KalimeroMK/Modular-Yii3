<?php

declare(strict_types=1);

namespace App\Modules\Tag\Application\Action;

use Psr\Http\Message\ResponseInterface;
use Yiisoft\DataResponse\ResponseFactory\JsonResponseFactory;
use Yiisoft\Http\Status;
use Yiisoft\Router\CurrentRoute;
use App\Modules\Tag\Application\Service\TagService;
use App\Modules\Tag\Domain\Exception\TagNotFoundException;

final readonly class DeleteAction
{
    public function __construct(
        private TagService $service,
        private JsonResponseFactory $responseFactory,
    ) {}

    public function __invoke(CurrentRoute $currentRoute): ResponseInterface
    {
        try {
            $this->service->delete((int) $currentRoute->getArgument('id'));
        } catch (TagNotFoundException $e) {
            return $this->responseFactory->createResponse(['error' => $e->getMessage()], Status::NOT_FOUND);
        }

        return $this->responseFactory->createResponse(null, Status::NO_CONTENT);
    }
}
