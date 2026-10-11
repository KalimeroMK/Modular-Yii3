<?php

declare(strict_types=1);

namespace App\Modules\Comment\Application\Action;

use Psr\Http\Message\ResponseInterface;
use Yiisoft\DataResponse\ResponseFactory\JsonResponseFactory;
use Yiisoft\Http\Status;
use App\Modules\Comment\Application\Dto\CommentInput;
use App\Modules\Comment\Application\Dto\CommentResource;
use App\Modules\Comment\Application\Service\CommentService;

final readonly class CreateAction
{
    public function __construct(
        private CommentService $service,
        private JsonResponseFactory $responseFactory,
    ) {}

    public function __invoke(CommentInput $input): ResponseInterface
    {
        $result = $input->getValidationResult();

        if (!$result->isValid()) {
            return $this->responseFactory->createResponse(
                ['error' => 'Validation failed', 'details' => $result->getErrorMessagesIndexedByProperty()],
                Status::UNPROCESSABLE_ENTITY,
            );
        }

        return $this->responseFactory->createResponse(
            ['data' => CommentResource::toArray($this->service->create($input))],
            Status::CREATED,
        );
    }
}
