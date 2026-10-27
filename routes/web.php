<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReservationController;
use Illuminate\Support\Facades\Route;

// ---------- Visiteur ----------
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/menu', [MenuController::class, 'index'])->name('menu.index'); // catalogue complet
Route::get('/menu/{menu}', [MenuController::class, 'show'])->name('menu.show'); // détails + commentaires
Route::get('/contact', [ContactController::class, 'index'])->name('contact.create');
Route::post('/contact', [ContactController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('contact.store');

// ---------- Client connecté ----------
Route::middleware('auth')->group(function () {
    Route::get('/reservations', [ReservationController::class, 'index'])->name('reservations.index');
    Route::get('/reservations/create', [ReservationController::class, 'create'])->name('reservations.create');
    // Disponibilité en temps réel (appelée en AJAX par le formulaire).
    Route::get('/reservations/availability', [ReservationController::class, 'availability'])->name('reservations.availability');
    Route::post('/reservations', [ReservationController::class, 'store'])->name('reservations.store');
    Route::patch('/reservations/{reservation}/cancel', [ReservationController::class, 'cancel'])->name('reservations.cancel');

    Route::post('/menu/{menu}/comments', [CommentController::class, 'store'])->name('comments.store');

    // profil Breeze
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ---------- Admin ----------
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');

    Route::resource('menus', Admin\MenuController::class)->except('show');
    Route::patch('menus/{menu}/toggle', [Admin\MenuController::class, 'toggle'])->name('menus.toggle');
    Route::resource('categories', Admin\CategoryController::class)->except('show');

    Route::get('reservations', [Admin\ReservationController::class, 'index'])->name('reservations.index');
    Route::patch('reservations/{reservation}', [Admin\ReservationController::class, 'updateStatus'])->name('reservations.status');
    // Présence du client (réservations confirmées uniquement).
    Route::patch('reservations/{reservation}/attendance', [Admin\ReservationController::class, 'attendance'])->name('reservations.attendance');
    // Masquage (jamais de suppression) et restauration.
    Route::patch('reservations/{reservation}/hide', [Admin\ReservationController::class, 'hide'])->name('reservations.hide');
    Route::patch('reservations/{reservation}/restore', [Admin\ReservationController::class, 'restore'])->name('reservations.restore');

    // Salle : tables physiques et clients sans réservation (walk-in).
    Route::resource('tables', Admin\TableController::class)->except('show');
    Route::patch('tables/{table}/toggle', [Admin\TableController::class, 'toggle'])->name('tables.toggle');
    Route::post('walk-ins', [Admin\WalkInController::class, 'store'])->name('walkins.store');
    Route::patch('walk-ins/{walkIn}/release', [Admin\WalkInController::class, 'release'])->name('walkins.release');

    Route::get('comments', [Admin\CommentController::class, 'index'])->name('comments.index');
    Route::patch('comments/{comment}/approve', [Admin\CommentController::class, 'approve'])->name('comments.approve');
    Route::patch('comments/{comment}/reject', [Admin\CommentController::class, 'reject'])->name('comments.reject');
    Route::patch('comments/{comment}/restore', [Admin\CommentController::class, 'restore'])->name('comments.restore');
    Route::delete('comments/{comment}', [Admin\CommentController::class, 'destroy'])->name('comments.destroy');

    Route::get('users', [Admin\UserController::class, 'index'])->name('users.index');
    Route::patch('users/{user}/block', [Admin\UserController::class, 'block'])->name('users.block');
    Route::patch('users/{user}/unblock', [Admin\UserController::class, 'unblock'])->name('users.unblock');
    Route::delete('users/{user}', [Admin\UserController::class, 'destroy'])->name('users.destroy');
});

Route::get('/dashboard', function () {
    // Redirige chaque utilisateur vers son espace adapté.
    if (auth()->user()->isAdmin()) {
        return redirect()->route('admin.dashboard');
    }

    return redirect()->route('reservations.index');
})->middleware('auth')->name('dashboard');

require __DIR__.'/auth.php';
