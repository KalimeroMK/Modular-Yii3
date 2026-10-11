<?php

declare(strict_types=1);

namespace App\Console;

use App\Core\Module\ModuleGenerator;
use App\Core\Module\MorphTargetChecker;
use App\Core\Module\Naming;
use App\Core\Module\YamlModuleParser;
use InvalidArgumentException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Yii\Console\ExitCode;

use function count;
use function explode;
use function implode;
use function sprintf;

#[AsCommand(
    name: 'module:make',
    description: 'Generates a single module without touching modules.yaml',
)]
final class ModuleMakeCommand extends Command
{
    public function __construct(
        private readonly YamlModuleParser $parser,
        private readonly ModuleGenerator $generator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::REQUIRED, 'Module name, for example Product')
            ->addOption(
                'field',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Field as name:type, for example --field "price:decimal:10,2"',
            )
            ->addOption(
                'relation',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Relation as name:type:Target, for example --relation "user:belongsTo:User"',
            )
            ->addOption('table', null, InputOption::VALUE_REQUIRED, 'Table name (defaults to the pluralised module name)')
            ->addOption('no-api', null, InputOption::VALUE_NONE, 'Skip HTTP actions and routes')
            ->addOption('no-tests', null, InputOption::VALUE_NONE, 'Skip generated tests')
            ->addOption('no-timestamps', null, InputOption::VALUE_NONE, 'Skip created_at/updated_at')
            ->addOption('soft-delete', null, InputOption::VALUE_NONE, 'Mark rows deleted instead of removing them')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Overwrite files that already exist');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $name = Naming::pascal((string) $input->getArgument('name'));

        /** @var list<string> $fieldOptions */
        $fieldOptions = $input->getOption('field');
        /** @var list<string> $relationOptions */
        $relationOptions = $input->getOption('relation');

        try {
            $configuration = [
                'fields' => $this->toMapping($fieldOptions, 'field'),
                'relations' => $this->toMapping($relationOptions, 'relation'),
                'api' => !$input->getOption('no-api'),
                'tests' => !$input->getOption('no-tests'),
                'timestamps' => !$input->getOption('no-timestamps'),
                'softDelete' => (bool) $input->getOption('soft-delete'),
            ];

            if ($input->getOption('table') !== null) {
                $configuration['table'] = (string) $input->getOption('table');
            }

            $modules = $this->parser->parse(['modules' => [$name => $configuration]]);
        } catch (InvalidArgumentException $e) {
            $io->error($e->getMessage());

            return ExitCode::DATAERR;
        }


        $statuses = $this->generator->generate($modules[0], (bool) $input->getOption('force'));
        $rows = [];

        foreach ($statuses as $path => $status) {
            $rows[] = [$status, $path];
        }

        $io->title($name);
        $io->table(['status', 'file'], $rows);

        foreach (MorphTargetChecker::missingTargets($modules) as $target => $columns) {
            $io->note(sprintf(
                '%s is the target of a polymorphic relation but is not part of this build. Make sure it has '
                . '`%s`, otherwise the relation queries columns that do not exist.',
                $target,
                implode('`, `', $columns),
            ));
        }
        $io->success('Done. Run `./yii migrate:up` to create the table.');

        return ExitCode::OK;
    }

    /**
     * Turns `name:rest` options into the mapping the YAML parser understands.
     *
     * @param list<string> $options
     *
     * @return array<string, string>
     */
    private function toMapping(array $options, string $kind): array
    {
        $mapping = [];

        foreach ($options as $option) {
            $parts = explode(':', $option, 2);

            if (count($parts) !== 2) {
                throw new InvalidArgumentException(
                    sprintf('Option --%s expects "name:definition", got "%s".', $kind, $option),
                );
            }

            $mapping[$parts[0]] = $parts[1];
        }

        return $mapping;
    }
}
