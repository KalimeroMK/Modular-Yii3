<?php

declare(strict_types=1);

namespace App\Core\Module;

use App\Core\Module\Definition\ModuleDefinition;

use function ksort;

/**
 * Finds polymorphic relations whose target module is not part of the same build.
 *
 * The generator can only add the `*_type` / `*_id` columns to a module it is generating, so a relation that
 * points outside the build would silently query columns that do not exist.
 */
final class MorphTargetChecker
{
    /**
     * @param list<ModuleDefinition> $definitions
     *
     * @return array<string, list<string>> Target module name mapped to the columns it needs.
     */
    public static function missingTargets(array $definitions): array
    {
        $generated = [];

        foreach ($definitions as $definition) {
            $generated[$definition->name] = true;
        }

        $missing = [];

        foreach ($definitions as $definition) {
            foreach ($definition->morphRelations() as $relation) {
                if (isset($generated[$relation->target])) {
                    continue;
                }

                $missing[$relation->target] = [
                    $relation->morphTypeColumn() . ': string:32',
                    $relation->foreignKey . ': integer',
                ];
            }
        }

        ksort($missing);

        return $missing;
    }
}
