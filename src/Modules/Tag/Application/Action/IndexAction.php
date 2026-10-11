<?php

declare(strict_types=1);

namespace App\Modules\Tag\Application\Action;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\DataResponse\ResponseFactory\JsonResponseFactory;
use App\Modules\Tag\Application\Dto\TagResource;
use App\Modules\Tag\Application\Service\TagService;

use function max;
use function min;

final readonly class IndexAction
{
    public function __construct(
        private TagService $service,
        private JsonResponseFactory $responseFactory,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $query = $request->getQueryParams();
        $limit = min(100, max(1, (int) ($query['limit'] ?? 20)));
        $offset = max(0, (int) ($query['offset'] ?? 0));

        return $this->responseFactory->createResponse([
            'data' => TagResource::toArrayList($this->service->list($limit, $offset)),
            'meta' => [
                'total' => $this->service->count(),
                'limit' => $limit,
                'offset' => $offset,
            ],
        ]);
    }
}
