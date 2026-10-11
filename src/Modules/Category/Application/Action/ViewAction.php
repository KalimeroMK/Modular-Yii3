<?php

declare(strict_types=1);

namespace App\Modules\Category\Application\Action;

use Psr\Http\Message\ResponseInterface;
use Yiisoft\DataResponse\ResponseFactory\JsonResponseFactory;
use Yiisoft\Http\Status;
use Yiisoft\Router\CurrentRoute;
use App\Modules\Category\Application\Dto\CategoryResource;
use App\Modules\Category\Application\Service\CategoryService;
use App\Modules\Category\Domain\Exception\CategoryNotFoundException;

final readonly class ViewAction
{
    public function __construct(
        private CategoryService $service,
        private JsonResponseFactory $responseFactory,
    ) {}

    public function __invoke(CurrentRoute $currentRoute): ResponseInterface
    {
        try {
            $category = $this->service->get((int) $currentRoute->getArgument('id'));
        } catch (CategoryNotFoundException $e) {
            return $this->responseFactory->createResponse(['error' => $e->getMessage()], Status::NOT_FOUND);
        }

        return $this->responseFactory->createResponse(['data' => CategoryResource::toArray($category)]);
    }
}
