<?php

use App\Exceptions\BusinessRuleException;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\TrackDeviceAndReferral;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [TrackDeviceAndReferral::class]);
        $middleware->alias(['active' => EnsureUserIsActive::class]);
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('dashboard.index'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (BusinessRuleException $e, Request $request) {
            return $request->expectsJson() ? null : back()->withInput()->with('error', $e->getMessage());
        });

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
