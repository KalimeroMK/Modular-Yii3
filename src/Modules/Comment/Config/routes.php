<?php

declare(strict_types=1);

use Yiisoft\Router\Group;
use Yiisoft\Router\Route;
use App\Modules\Comment\Application\Action\CreateAction;
use App\Modules\Comment\Application\Action\DeleteAction;
use App\Modules\Comment\Application\Action\IndexAction;
use App\Modules\Comment\Application\Action\UpdateAction;
use App\Modules\Comment\Application\Action\ViewAction;

return [
    Group::create('/api/comments')
        ->routes(
            Route::get('')
                ->action(IndexAction::class)
                ->name('comment/index'),
            Route::get('/{id:\d+}')
                ->action(ViewAction::class)
                ->name('comment/view'),
            Route::post('')
                ->action(CreateAction::class)
                ->name('comment/create'),
            Route::put('/{id:\d+}')
                ->action(UpdateAction::class)
                ->name('comment/update'),
            Route::delete('/{id:\d+}')
                ->action(DeleteAction::class)
                ->name('comment/delete'),
        ),
];
