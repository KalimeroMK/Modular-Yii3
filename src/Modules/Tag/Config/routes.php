<?php

declare(strict_types=1);

use Yiisoft\Router\Group;
use Yiisoft\Router\Route;
use App\Modules\Tag\Application\Action\CreateAction;
use App\Modules\Tag\Application\Action\DeleteAction;
use App\Modules\Tag\Application\Action\IndexAction;
use App\Modules\Tag\Application\Action\UpdateAction;
use App\Modules\Tag\Application\Action\ViewAction;

return [
    Group::create('/api/tags')
        ->routes(
            Route::get('')
                ->action(IndexAction::class)
                ->name('tag/index'),
            Route::get('/{id:\d+}')
                ->action(ViewAction::class)
                ->name('tag/view'),
            Route::post('')
                ->action(CreateAction::class)
                ->name('tag/create'),
            Route::put('/{id:\d+}')
                ->action(UpdateAction::class)
                ->name('tag/update'),
            Route::delete('/{id:\d+}')
                ->action(DeleteAction::class)
                ->name('tag/delete'),
        ),
];
