<?php

use App\Http\Middleware\SetLocale;
use App\Http\Middleware\VerifyTelegramWebhook;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('retention:prune')
            ->dailyAt('03:00')
            ->withoutOverlapping(30);
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo('/backoffice/login');
        $middleware->redirectUsersTo('/backoffice');
        $middleware->alias([
            'set.locale' => SetLocale::class,
            'verify.telegram' => VerifyTelegramWebhook::class,
        ]);
    })
    ->withExceptions(function (): void {
        //
    })->create();
