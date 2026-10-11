<?php

declare(strict_types=1);

namespace App\Modules\Product\Application\Dto;

use Yiisoft\Input\Http\AbstractInput;
use Yiisoft\Input\Http\Attribute\Data\FromBody;
use Yiisoft\Validator\Rule\BooleanValue;
use Yiisoft\Validator\Rule\In;
use Yiisoft\Validator\Rule\Integer;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Number;
use Yiisoft\Validator\Rule\Required;

/**
 * Hydrated from the request body and validated before the action runs.
 */
#[FromBody]
final class ProductInput extends AbstractInput
{
    #[Required]
    #[Length(max: 120)]
    public string $name = '';

    #[Required]
    #[Length(max: 150)]
    public string $slug = '';

    #[Required]
    #[Number]
    public string $price = '';

    #[BooleanValue]
    public bool $is_active = false;

    #[Required]
    #[In(['draft', 'published', 'archived'])]
    public string $status = 'draft';

    public ?array $metadata = null;

    #[Length(max: 65535, skipOnEmpty: true)]
    public ?string $description = null;

    #[Length(max: 64, skipOnEmpty: true)]
    public ?string $published_at = null;

    #[Required]
    #[Integer]
    public int $category_id = 0;
}
