<?php

declare(strict_types=1);

namespace App\Modules\Category\Application\Action;

use Psr\Http\Message\ResponseInterface;
use Yiisoft\DataResponse\ResponseFactory\JsonResponseFactory;
use Yiisoft\Http\Status;
use App\Modules\Category\Application\Dto\CategoryInput;
use App\Modules\Category\Application\Dto\CategoryResource;
use App\Modules\Category\Application\Service\CategoryService;

final readonly class CreateAction
{
    public function __construct(
        private CategoryService $service,
        private JsonResponseFactory $responseFactory,
    ) {}

    public function __invoke(CategoryInput $input): ResponseInterface
    {
        $result = $input->getValidationResult();

        if (!$result->isValid()) {
            return $this->responseFactory->createResponse(
                ['error' => 'Validation failed', 'details' => $result->getErrorMessagesIndexedByProperty()],
                Status::UNPROCESSABLE_ENTITY,
            );
        }

        return $this->responseFactory->createResponse(
            ['data' => CategoryResource::toArray($this->service->create($input))],
            Status::CREATED,
        );
    }
}
