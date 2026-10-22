<?php

use App\Http\Middleware\AdminMiddleware;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias(['admin' => AdminMiddleware::class]);
    })
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withSchedule(function (Schedule $schedule): void {
        // Expiration des demandes restées en attente (délai configurable).
        // Une demande expirée libère la journée et ne compte jamais comme
        // une absence.
        $schedule->command('reservations:expire-pending')
            ->hourly()
            ->withoutOverlapping();

        // Blocage automatique des clients qui atteignent le seuil d'absences
        // vérifiées. Le déblocage reste une décision de l'administrateur.
        $schedule->command('reservations:refresh-blocks')
            ->dailyAt('03:30')
            ->withoutOverlapping();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
