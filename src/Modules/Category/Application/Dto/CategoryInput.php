<?php

declare(strict_types=1);

namespace App\Modules\Category\Application\Dto;

use Yiisoft\Input\Http\AbstractInput;
use Yiisoft\Input\Http\Attribute\Data\FromBody;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Required;

/**
 * Hydrated from the request body and validated before the action runs.
 */
#[FromBody]
final class CategoryInput extends AbstractInput
{
    #[Required]
    #[Length(max: 120)]
    public string $name = '';

    #[Required]
    #[Length(max: 150)]
    public string $slug = '';
}
