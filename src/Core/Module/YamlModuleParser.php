<?php

declare(strict_types=1);

namespace App\Core\Module;

use App\Core\Module\Definition\Field;
use App\Core\Module\Definition\ModuleDefinition;
use App\Core\Module\Definition\Relation;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Yaml\Yaml;

use function is_array;
use function is_file;
use function is_string;
use function sprintf;

/**
 * Turns `modules.yaml` into module definitions.
 */
final class YamlModuleParser
{
    /**
     * @return list<ModuleDefinition>
     */
    public function parseFile(string $path): array
    {
        if (!is_file($path)) {
            throw new RuntimeException(sprintf('Module definition file "%s" does not exist.', $path));
        }

        $parsed = Yaml::parseFile($path);

        if (!is_array($parsed)) {
            throw new InvalidArgumentException(sprintf('"%s" does not contain a YAML mapping.', $path));
        }

        return $this->parse($parsed);
    }

    /**
     * @param array<array-key, mixed> $data
     *
     * @return list<ModuleDefinition>
     */
    public function parse(array $data): array
    {
        $modules = $data['modules'] ?? null;

        if (!is_array($modules) || $modules === []) {
            throw new InvalidArgumentException('The YAML file must contain a non-empty `modules:` mapping.');
        }

        $definitions = [];

        foreach ($modules as $name => $configuration) {
            if (!is_string($name)) {
                throw new InvalidArgumentException('Module names must be strings.');
            }

            $definitions[] = $this->parseModule($name, is_array($configuration) ? $configuration : []);
        }

        return $definitions;
    }

    /**
     * @param array<array-key, mixed> $configuration
     */
    private function parseModule(string $name, array $configuration): ModuleDefinition
    {
        $name = Naming::pascal($name);

        try {
            $fields = $this->parseFields($configuration['fields'] ?? []);
            $relations = $this->parseRelations($configuration['relations'] ?? [], $name);
        } catch (InvalidArgumentException $e) {
            throw new InvalidArgumentException(sprintf('Module "%s": %s', $name, $e->getMessage()), 0, $e);
        }

        if ($fields === [] && $relations === []) {
            throw new InvalidArgumentException(sprintf('Module "%s" has neither fields nor relations.', $name));
        }

        return new ModuleDefinition(
            $name,
            (string) ($configuration['table'] ?? Naming::tableName($name)),
            $fields,
            $relations,
            (bool) ($configuration['api'] ?? true),
            (bool) ($configuration['tests'] ?? true),
            (bool) ($configuration['timestamps'] ?? true),
            (bool) ($configuration['softDelete'] ?? $configuration['soft_delete'] ?? false),
        );
    }

    /**
     * @return list<Field>
     */
    private function parseFields(mixed $fields): array
    {
        if (!is_array($fields)) {
            throw new InvalidArgumentException('`fields` must be a mapping of `name: type`.');
        }

        $result = [];

        foreach ($fields as $name => $definition) {
            $name = Naming::snake((string) $name);

            $result[] = is_array($definition)
                ? Field::fromArray($name, $definition)
                : Field::fromShortForm($name, (string) $definition);
        }

        return $result;
    }

    /**
     * @return list<Relation>
     */
    private function parseRelations(mixed $relations, string $moduleName): array
    {
        if (!is_array($relations)) {
            throw new InvalidArgumentException('`relations` must be a mapping of `name: type:Target`.');
        }

        $result = [];

        foreach ($relations as $name => $definition) {
            $name = Naming::camel((string) $name);

            $result[] = is_array($definition)
                ? Relation::fromArray($name, $definition, $moduleName)
                : Relation::fromShortForm($name, (string) $definition, $moduleName);
        }

        return $result;
    }
}
