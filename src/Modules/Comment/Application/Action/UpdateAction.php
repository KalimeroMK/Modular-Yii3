<?php

declare(strict_types=1);

namespace App\Modules\Comment\Application\Action;

use Psr\Http\Message\ResponseInterface;
use Yiisoft\DataResponse\ResponseFactory\JsonResponseFactory;
use Yiisoft\Http\Status;
use Yiisoft\Router\CurrentRoute;
use App\Modules\Comment\Application\Dto\CommentInput;
use App\Modules\Comment\Application\Dto\CommentResource;
use App\Modules\Comment\Application\Service\CommentService;
use App\Modules\Comment\Domain\Exception\CommentNotFoundException;

final readonly class UpdateAction
{
    public function __construct(
        private CommentService $service,
        private JsonResponseFactory $responseFactory,
    ) {}

    public function __invoke(CommentInput $input, CurrentRoute $currentRoute): ResponseInterface
    {
        $result = $input->getValidationResult();

        if (!$result->isValid()) {
            return $this->responseFactory->createResponse(
                ['error' => 'Validation failed', 'details' => $result->getErrorMessagesIndexedByProperty()],
                Status::UNPROCESSABLE_ENTITY,
            );
        }

        try {
            $comment = $this->service->update((int) $currentRoute->getArgument('id'), $input);
        } catch (CommentNotFoundException $e) {
            return $this->responseFactory->createResponse(['error' => $e->getMessage()], Status::NOT_FOUND);
        }

        return $this->responseFactory->createResponse(['data' => CommentResource::toArray($comment)]);
    }
}
