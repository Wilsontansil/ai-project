<?php

use App\Http\Middleware\SetLocale;
use App\Http\Middleware\SingleSession;
use App\Http\Middleware\VerifyTelegramWebhook;
use App\Http\Middleware\VerifyWhatsAppWebhook;
use App\Http\Middleware\VerifyLiveChatWebhook;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

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
            'single.session' => SingleSession::class,
            'verify.telegram' => VerifyTelegramWebhook::class,
            'verify.whatsapp' => VerifyWhatsAppWebhook::class,
            'verify.livechat' => VerifyLiveChatWebhook::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // API routes: always return JSON, never expose internals.
        $exceptions->render(function (\Throwable $e, Request $request) {
            if (!$request->is('api/*') && !$request->expectsJson()) {
                return null; // Let Laravel handle web routes with default views.
            }

            $status = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;

            $payload = ['error' => match (true) {
                $status === 404 => 'Not found.',
                $status === 403 => 'Forbidden.',
                $status === 429 => 'Too many requests.',
                $status >= 500   => 'Internal server error.',
                default          => 'An error occurred.',
            }];

            // Only expose the real message in debug mode.
            if (config('app.debug')) {
                $payload['debug'] = [
                    'message' => $e->getMessage(),
                    'file'    => $e->getFile() . ':' . $e->getLine(),
                ];
            }

            return response()->json($payload, $status);
        });
    })->create();
