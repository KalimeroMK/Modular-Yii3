<?php

declare(strict_types=1);

use App\Web;
use Yiisoft\Csrf\CsrfTokenMiddleware;
use Yiisoft\Router\Group;
use Yiisoft\Router\Route;

return [
    // CSRF protection belongs to the browser-facing routes. Module API groups are stateless and opt out by
    // not being part of this group.
    Group::create()
        ->middleware(CsrfTokenMiddleware::class)
        ->routes(
            Route::get('/')
                ->action(Web\HomePage\Action::class)
                ->name('home'),
        ),
];
