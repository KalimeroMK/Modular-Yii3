<?php

declare(strict_types=1);

namespace App\Modules\Tag\Application\Action;

use Psr\Http\Message\ResponseInterface;
use Yiisoft\DataResponse\ResponseFactory\JsonResponseFactory;
use Yiisoft\Http\Status;
use App\Modules\Tag\Application\Dto\TagInput;
use App\Modules\Tag\Application\Dto\TagResource;
use App\Modules\Tag\Application\Service\TagService;

final readonly class CreateAction
{
    public function __construct(
        private TagService $service,
        private JsonResponseFactory $responseFactory,
    ) {}

    public function __invoke(TagInput $input): ResponseInterface
    {
        $result = $input->getValidationResult();

        if (!$result->isValid()) {
            return $this->responseFactory->createResponse(
                ['error' => 'Validation failed', 'details' => $result->getErrorMessagesIndexedByProperty()],
                Status::UNPROCESSABLE_ENTITY,
            );
        }

        return $this->responseFactory->createResponse(
            ['data' => TagResource::toArray($this->service->create($input))],
            Status::CREATED,
        );
    }
}
