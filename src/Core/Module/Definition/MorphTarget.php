<?php

declare(strict_types=1);

namespace App\Core\Module\Definition;

use App\Core\Module\Naming;

/**
 * The receiving end of one or more polymorphic relations.
 *
 * `Comment` with `commentable_type` / `commentable_id` columns is one morph target named `commentable`,
 * whose owners are every module that declared `morphMany:Comment:commentable`.
 */
final readonly class MorphTarget
{
    /**
     * @param array<string, string> $owners Stored type value mapped to the owning module name.
     */
    public function __construct(
        public string $name,
        public array $owners,
    ) {}

    public function typeColumn(): string
    {
        return $this->name . '_type';
    }

    public function idColumn(): string
    {
        return $this->name . '_id';
    }

    public function resolverClass(): string
    {
        return Naming::pascal($this->name) . 'Resolver';
    }

    /**
     * @param array<string, string> $owners
     */
    public function withOwners(array $owners): self
    {
        return new self($this->name, $owners);
    }
}
