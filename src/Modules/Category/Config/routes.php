<?php

declare(strict_types=1);

use Yiisoft\Router\Group;
use Yiisoft\Router\Route;
use App\Modules\Category\Application\Action\CreateAction;
use App\Modules\Category\Application\Action\DeleteAction;
use App\Modules\Category\Application\Action\IndexAction;
use App\Modules\Category\Application\Action\UpdateAction;
use App\Modules\Category\Application\Action\ViewAction;

return [
    Group::create('/api/categories')
        ->routes(
            Route::get('')
                ->action(IndexAction::class)
                ->name('category/index'),
            Route::get('/{id:\d+}')
                ->action(ViewAction::class)
                ->name('category/view'),
            Route::post('')
                ->action(CreateAction::class)
                ->name('category/create'),
            Route::put('/{id:\d+}')
                ->action(UpdateAction::class)
                ->name('category/update'),
            Route::delete('/{id:\d+}')
                ->action(DeleteAction::class)
                ->name('category/delete'),
        ),
];
