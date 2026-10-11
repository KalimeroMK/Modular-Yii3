<?php

declare(strict_types=1);

namespace App\Modules\Product\Application\Action;

use Psr\Http\Message\ResponseInterface;
use Yiisoft\DataResponse\ResponseFactory\JsonResponseFactory;
use Yiisoft\Http\Status;
use App\Modules\Product\Application\Dto\ProductInput;
use App\Modules\Product\Application\Dto\ProductResource;
use App\Modules\Product\Application\Service\ProductService;

final readonly class CreateAction
{
    public function __construct(
        private ProductService $service,
        private JsonResponseFactory $responseFactory,
    ) {}

    public function __invoke(ProductInput $input): ResponseInterface
    {
        $result = $input->getValidationResult();

        if (!$result->isValid()) {
            return $this->responseFactory->createResponse(
                ['error' => 'Validation failed', 'details' => $result->getErrorMessagesIndexedByProperty()],
                Status::UNPROCESSABLE_ENTITY,
            );
        }

        return $this->responseFactory->createResponse(
            ['data' => ProductResource::toArray($this->service->create($input))],
            Status::CREATED,
        );
    }
}
