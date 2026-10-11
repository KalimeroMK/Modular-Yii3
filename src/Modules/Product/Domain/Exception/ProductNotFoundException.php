<?php

declare(strict_types=1);

namespace App\Modules\Product\Domain\Exception;

use RuntimeException;
use Yiisoft\FriendlyException\FriendlyExceptionInterface;

use function sprintf;

final class ProductNotFoundException extends RuntimeException implements FriendlyExceptionInterface
{
    public static function withId(int $id): self
    {
        return new self(sprintf('Product with id %d was not found.', $id));
    }

    public function getName(): string
    {
        return 'Product not found';
    }

    public function getSolution(): ?string
    {
        return 'Check the identifier in the request, or list the available records first.';
    }
}
