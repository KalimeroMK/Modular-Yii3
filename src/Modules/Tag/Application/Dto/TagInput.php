<?php

declare(strict_types=1);

namespace App\Modules\Tag\Application\Dto;

use Yiisoft\Input\Http\AbstractInput;
use Yiisoft\Input\Http\Attribute\Data\FromBody;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Required;

/**
 * Hydrated from the request body and validated before the action runs.
 */
#[FromBody]
final class TagInput extends AbstractInput
{
    #[Required]
    #[Length(max: 60)]
    public string $name = '';

    #[Required]
    #[Length(max: 7)]
    public string $color = '';
}
