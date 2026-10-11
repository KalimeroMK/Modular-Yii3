<?php

declare(strict_types=1);

namespace App\Modules\Category\Application\Action;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\DataResponse\ResponseFactory\JsonResponseFactory;
use App\Modules\Category\Application\Dto\CategoryResource;
use App\Modules\Category\Application\Service\CategoryService;

use function max;
use function min;

final readonly class IndexAction
{
    public function __construct(
        private CategoryService $service,
        private JsonResponseFactory $responseFactory,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $query = $request->getQueryParams();
        $limit = min(100, max(1, (int) ($query['limit'] ?? 20)));
        $offset = max(0, (int) ($query['offset'] ?? 0));

        return $this->responseFactory->createResponse([
            'data' => CategoryResource::toArrayList($this->service->list($limit, $offset)),
            'meta' => [
                'total' => $this->service->count(),
                'limit' => $limit,
                'offset' => $offset,
            ],
        ]);
    }
}
