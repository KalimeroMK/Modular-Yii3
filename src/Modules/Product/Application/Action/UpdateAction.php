<?php

declare(strict_types=1);

namespace App\Modules\Product\Application\Action;

use Psr\Http\Message\ResponseInterface;
use Yiisoft\DataResponse\ResponseFactory\JsonResponseFactory;
use Yiisoft\Http\Status;
use Yiisoft\Router\CurrentRoute;
use App\Modules\Product\Application\Dto\ProductInput;
use App\Modules\Product\Application\Dto\ProductResource;
use App\Modules\Product\Application\Service\ProductService;
use App\Modules\Product\Domain\Exception\ProductNotFoundException;

final readonly class UpdateAction
{
    public function __construct(
        private ProductService $service,
        private JsonResponseFactory $responseFactory,
    ) {}

    public function __invoke(ProductInput $input, CurrentRoute $currentRoute): ResponseInterface
    {
        $result = $input->getValidationResult();

        if (!$result->isValid()) {
            return $this->responseFactory->createResponse(
                ['error' => 'Validation failed', 'details' => $result->getErrorMessagesIndexedByProperty()],
                Status::UNPROCESSABLE_ENTITY,
            );
        }

        try {
            $product = $this->service->update((int) $currentRoute->getArgument('id'), $input);
        } catch (ProductNotFoundException $e) {
            return $this->responseFactory->createResponse(['error' => $e->getMessage()], Status::NOT_FOUND);
        }

        return $this->responseFactory->createResponse(['data' => ProductResource::toArray($product)]);
    }
}
