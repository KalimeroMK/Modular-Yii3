<?php

declare(strict_types=1);

namespace App\Console;

use App\Core\Module\Definition\ModuleDefinition;
use App\Core\Module\ModuleGenerator;
use App\Core\Module\YamlModuleParser;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Yii\Console\ExitCode;

use function array_fill_keys;
use function array_keys;
use function count;
use function sprintf;

#[AsCommand(
    name: 'module:build',
    description: 'Generates every module described in modules.yaml',
)]
final class ModuleBuildCommand extends Command
{
    public function __construct(
        private readonly Aliases $aliases,
        private readonly YamlModuleParser $parser,
        private readonly ModuleGenerator $generator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('file', InputArgument::OPTIONAL, 'Path to the YAML definition', 'modules.yaml')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Overwrite files that already exist')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'List the files without writing them');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $file = (string) $input->getArgument('file');
        $path = str_starts_with($file, '/') ? $file : $this->aliases->get('@root') . '/' . $file;

        try {
            $modules = $this->parser->parseFile($path);
        } catch (InvalidArgumentException|RuntimeException $e) {
            $io->error($e->getMessage());

            return ExitCode::DATAERR;
        }

        $io->title(sprintf('Building %d module(s) from %s', count($modules), $file));

        $force = (bool) $input->getOption('force');
        $dryRun = (bool) $input->getOption('dry-run');

        foreach ($modules as $module) {
            $this->reportModule($io, $module, $dryRun, $force);
        }

        if ($dryRun) {
            $io->note('Dry run: nothing was written.');

            return ExitCode::OK;
        }

        $io->success('Done. Run `./yii migrate:up` to create the tables.');

        return ExitCode::OK;
    }

    private function reportModule(SymfonyStyle $io, ModuleDefinition $module, bool $dryRun, bool $force): void
    {
        $io->section($module->name);

        $statuses = $dryRun
            ? array_fill_keys(array_keys($this->generator->files($module)), 'planned')
            : $this->generator->generate($module, $force);

        $rows = [];

        foreach ($statuses as $path => $status) {
            $rows[] = [$status, $path];
        }

        $io->table(['status', 'file'], $rows);
    }
}
