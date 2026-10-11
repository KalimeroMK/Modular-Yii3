<?php

declare(strict_types=1);

use App\Core\Module\ModuleGenerator;
use App\Core\Module\StubRenderer;
use Yiisoft\Aliases\Aliases;

return [
    StubRenderer::class => static fn(Aliases $aliases): StubRenderer => new StubRenderer(
        $aliases->get('@root/stubs/module'),
    ),

    ModuleGenerator::class => static fn(Aliases $aliases, StubRenderer $renderer): ModuleGenerator
        => new ModuleGenerator($aliases->get('@root'), $renderer),
];
