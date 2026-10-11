<?php

declare(strict_types=1);

namespace App\Core\Module;

use App\Core\Module\Definition\ModuleDefinition;
use RuntimeException;

use function basename;
use function dirname;
use function file_exists;
use function file_put_contents;
use function glob;
use function gmdate;
use function is_dir;
use function mkdir;
use function sprintf;
use function str_replace;

/**
 * Writes a whole module to disk from its definition.
 *
 * Generation is idempotent: an existing file is left alone unless `$force` is set, and the migration class name is
 * reused when the module already has one, so re-running `module:build` never produces a second migration.
 */
final readonly class ModuleGenerator
{
    public const STATUS_CREATED = 'created';
    public const STATUS_OVERWRITTEN = 'overwritten';
    public const STATUS_SKIPPED = 'skipped';

    public function __construct(
        private string $projectRoot,
        private StubRenderer $renderer,
    ) {}

    /**
     * @return array<string, string> Relative file path mapped to one of the `STATUS_*` constants.
     */
    public function generate(ModuleDefinition $module, bool $force = false): array
    {
        $statuses = [];

        foreach ($this->files($module) as $path => $content) {
            $absolutePath = $this->projectRoot . '/' . $path;
            $exists = file_exists($absolutePath);

            if ($exists && !$force) {
                $statuses[$path] = self::STATUS_SKIPPED;
                continue;
            }

            $directory = dirname($absolutePath);

            if (!is_dir($directory) && !mkdir($directory, 0o755, true) && !is_dir($directory)) {
                throw new RuntimeException(sprintf('Unable to create directory "%s".', $directory));
            }

            if (file_put_contents($absolutePath, $content) === false) {
                throw new RuntimeException(sprintf('Unable to write "%s".', $absolutePath));
            }

            $statuses[$path] = $exists ? self::STATUS_OVERWRITTEN : self::STATUS_CREATED;
        }

        return $statuses;
    }

    /**
     * The complete file plan for a module: relative path mapped to rendered content.
     *
     * @return array<string, string>
     */
    public function files(ModuleDefinition $module): array
    {
        $fragments = new CodeFragments($module);
        $placeholders = $this->placeholders($module, $fragments);
        $moduleDirectory = 'src/Modules/' . $module->name;
        $testDirectory = 'tests/Modules/' . $module->name;
        $name = $module->name;

        $files = [
            $moduleDirectory . '/Domain/Model/' . $name . '.php' => $this->renderer->render('Model', $placeholders),
            $moduleDirectory . '/Domain/Repository/' . $name . 'RepositoryInterface.php'
                => $this->renderer->render('RepositoryInterface', $placeholders),
            $moduleDirectory . '/Domain/Exception/' . $name . 'NotFoundException.php'
                => $this->renderer->render('NotFoundException', $placeholders),
            $moduleDirectory . '/Infrastructure/Persistence/ActiveRecord' . $name . 'Repository.php'
                => $this->renderer->render('Repository', $placeholders),
            $moduleDirectory . '/Infrastructure/Migration/' . $placeholders['migrationClass'] . '.php'
                => $this->renderer->render('Migration', $placeholders),
            $moduleDirectory . '/Application/Service/' . $name . 'Service.php'
                => $this->renderer->render('Service', $placeholders),
            $moduleDirectory . '/Application/Dto/' . $name . 'Input.php'
                => $this->renderer->render('Input', $placeholders),
            $moduleDirectory . '/Application/Dto/' . $name . 'Resource.php'
                => $this->renderer->render('Resource', $placeholders),
            $moduleDirectory . '/Config/di.php' => $this->renderer->render('Config/di', $placeholders),
        ];

        foreach ($module->enumFields() as $field) {
            $enumName = $field->enumClass($name);
            $files[$moduleDirectory . '/Domain/Enum/' . $enumName . '.php'] = $this->renderer->render(
                'Enum',
                [...$placeholders, 'enumName' => $enumName, 'enumCases' => $fragments->enumCases($field)],
            );
        }

        if ($module->api) {
            foreach (['IndexAction', 'ViewAction', 'CreateAction', 'UpdateAction', 'DeleteAction'] as $action) {
                $files[$moduleDirectory . '/Application/Action/' . $action . '.php']
                    = $this->renderer->render('Action/' . $action, $placeholders);
            }

            $files[$moduleDirectory . '/Config/routes.php'] = $this->renderer->render('Config/routes', $placeholders);
        }

        if ($module->tests) {
            $files[$testDirectory . '/' . $name . 'RepositoryTest.php']
                = $this->renderer->render('Test/RepositoryTest', $placeholders);
            $files[$testDirectory . '/' . $name . 'ServiceTest.php']
                = $this->renderer->render('Test/ServiceTest', $placeholders);
        }

        return $files;
    }

    /**
     * @return array<string, string>
     */
    private function placeholders(ModuleDefinition $module, CodeFragments $fragments): array
    {
        $variable = $module->variable();

        return [
            'namespace' => $module->namespace(),
            'module' => $module->name,
            'moduleVariable' => $variable,
            'table' => $module->table,
            'tableExpression' => '{{%' . $module->table . '}}',
            'routeSegment' => $module->routeSegment(),
            'routeName' => Naming::kebab($module->name),
            'migrationClass' => $this->migrationClass($module),
            'modelUses' => $fragments->modelUses(),
            'modelProperties' => $fragments->modelProperties(),
            'relationArms' => $fragments->relationArms(),
            'migrationColumns' => $fragments->migrationColumns(),
            'migrationIndexes' => $fragments->migrationIndexes(),
            'migrationForeignKeys' => $fragments->migrationForeignKeys(),
            'migrationJunctionTables' => $fragments->migrationJunctionTables(),
            'migrationJunctionDrops' => $fragments->migrationJunctionDrops(),
            'inputUses' => $fragments->inputUses(),
            'inputProperties' => $fragments->inputProperties(),
            'assignments' => $fragments->assignments(),
            'resourceFields' => $fragments->resourceFields(),
            'notDeletedFilter' => $module->softDelete ? "\n            ->andWhere(['deleted_at' => null])" : '',
            'timestampBlock' => $this->timestampBlock($module, $variable),
            'deleteBody' => $this->deleteBody($module, $variable),
            'repositoryUses' => $fragments->repositoryUses(),
            'serviceUses' => $fragments->serviceUses(),
            'repositoryTestUses' => $fragments->repositoryTestUses($this->migrationClass($module)),
            'testAssignments' => $fragments->testAssignments($variable, false, true, 'suffix'),
            'testAssertionsLoaded' => $fragments->testAssertions('loaded'),
            'testAssertionsCreated' => $fragments->testAssertions($variable),
            'testAssertionsUpdated' => $fragments->testAssertions('updated', true),
            'testInputAssignments' => $fragments->testAssignments('input', false, false),
            'testUpdatedInputAssignments' => $fragments->testAssignments('input', true, false),
        ];
    }

    private function timestampBlock(ModuleDefinition $module, string $variable): string
    {
        if (!$module->timestamps) {
            return '';
        }

        return sprintf(
            "        \$now = new DateTimeImmutable();\n\n"
            . "        if (\$%s->isNew()) {\n"
            . "            \$%s->created_at = \$now;\n"
            . "        }\n\n"
            . "        \$%s->updated_at = \$now;\n\n",
            $variable,
            $variable,
            $variable,
        );
    }

    private function deleteBody(ModuleDefinition $module, string $variable): string
    {
        if (!$module->softDelete) {
            return sprintf("        \$%s->delete();\n", $variable);
        }

        return sprintf(
            "        \$%s->deleted_at = new DateTimeImmutable();\n"
            . "        \$%s->save();\n",
            $variable,
            $variable,
        );
    }

    /**
     * Reuses the migration class the module already has, so regenerating does not add a second one.
     */
    private function migrationClass(ModuleDefinition $module): string
    {
        $suffix = 'Create' . Naming::pascal($module->table) . 'Table';
        $existing = glob(sprintf(
            '%s/src/Modules/%s/Infrastructure/Migration/M*%s.php',
            $this->projectRoot,
            $module->name,
            $suffix,
        ));

        if ($existing !== false && $existing !== []) {
            return str_replace('.php', '', basename($existing[0]));
        }

        return 'M' . gmdate('ymdHis') . $suffix;
    }
}
