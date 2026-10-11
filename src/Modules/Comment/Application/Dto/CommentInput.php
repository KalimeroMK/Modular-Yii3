<?php

declare(strict_types=1);

namespace App\Modules\Comment\Application\Dto;

use Yiisoft\Input\Http\AbstractInput;
use Yiisoft\Input\Http\Attribute\Data\FromBody;
use Yiisoft\Validator\Rule\Integer;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Required;

/**
 * Hydrated from the request body and validated before the action runs.
 */
#[FromBody]
final class CommentInput extends AbstractInput
{
    #[Required]
    #[Length(max: 120)]
    public string $author = '';

    #[Required]
    #[Length(max: 65535)]
    public string $body = '';

    #[Required]
    #[Length(max: 32)]
    public string $commentable_type = '';

    #[Required]
    #[Integer]
    public int $commentable_id = 0;
}
