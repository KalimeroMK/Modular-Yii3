<?php

declare(strict_types=1);

namespace App\Modules\Tag\Application\Action;

use Psr\Http\Message\ResponseInterface;
use Yiisoft\DataResponse\ResponseFactory\JsonResponseFactory;
use Yiisoft\Http\Status;
use Yiisoft\Router\CurrentRoute;
use App\Modules\Tag\Application\Dto\TagResource;
use App\Modules\Tag\Application\Service\TagService;
use App\Modules\Tag\Domain\Exception\TagNotFoundException;

final readonly class ViewAction
{
    public function __construct(
        private TagService $service,
        private JsonResponseFactory $responseFactory,
    ) {}

    public function __invoke(CurrentRoute $currentRoute): ResponseInterface
    {
        try {
            $tag = $this->service->get((int) $currentRoute->getArgument('id'));
        } catch (TagNotFoundException $e) {
            return $this->responseFactory->createResponse(['error' => $e->getMessage()], Status::NOT_FOUND);
        }

        return $this->responseFactory->createResponse(['data' => TagResource::toArray($tag)]);
    }
}
