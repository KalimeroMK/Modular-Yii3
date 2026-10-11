<?php

declare(strict_types=1);

namespace App\Modules\Category\Application\Action;

use Psr\Http\Message\ResponseInterface;
use Yiisoft\DataResponse\ResponseFactory\JsonResponseFactory;
use Yiisoft\Http\Status;
use Yiisoft\Router\CurrentRoute;
use App\Modules\Category\Application\Dto\CategoryInput;
use App\Modules\Category\Application\Dto\CategoryResource;
use App\Modules\Category\Application\Service\CategoryService;
use App\Modules\Category\Domain\Exception\CategoryNotFoundException;

final readonly class UpdateAction
{
    public function __construct(
        private CategoryService $service,
        private JsonResponseFactory $responseFactory,
    ) {}

    public function __invoke(CategoryInput $input, CurrentRoute $currentRoute): ResponseInterface
    {
        $result = $input->getValidationResult();

        if (!$result->isValid()) {
            return $this->responseFactory->createResponse(
                ['error' => 'Validation failed', 'details' => $result->getErrorMessagesIndexedByProperty()],
                Status::UNPROCESSABLE_ENTITY,
            );
        }

        try {
            $category = $this->service->update((int) $currentRoute->getArgument('id'), $input);
        } catch (CategoryNotFoundException $e) {
            return $this->responseFactory->createResponse(['error' => $e->getMessage()], Status::NOT_FOUND);
        }

        return $this->responseFactory->createResponse(['data' => CategoryResource::toArray($category)]);
    }
}
