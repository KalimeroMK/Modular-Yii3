<?php

declare(strict_types=1);

use App\Console;

return [
    'hello' => Console\HelloCommand::class,
    'module:make' => Console\ModuleMakeCommand::class,
    'module:build' => Console\ModuleBuildCommand::class,
];
