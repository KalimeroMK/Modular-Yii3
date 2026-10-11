<?php

declare(strict_types=1);

use Yiisoft\Router\Group;
use Yiisoft\Router\Route;
use App\Modules\Product\Application\Action\CreateAction;
use App\Modules\Product\Application\Action\DeleteAction;
use App\Modules\Product\Application\Action\IndexAction;
use App\Modules\Product\Application\Action\UpdateAction;
use App\Modules\Product\Application\Action\ViewAction;

return [
    Group::create('/api/products')
        ->routes(
            Route::get('')
                ->action(IndexAction::class)
                ->name('product/index'),
            Route::get('/{id:\d+}')
                ->action(ViewAction::class)
                ->name('product/view'),
            Route::post('')
                ->action(CreateAction::class)
                ->name('product/create'),
            Route::put('/{id:\d+}')
                ->action(UpdateAction::class)
                ->name('product/update'),
            Route::delete('/{id:\d+}')
                ->action(DeleteAction::class)
                ->name('product/delete'),
        ),
];
