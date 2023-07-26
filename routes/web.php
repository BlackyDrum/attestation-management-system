<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return redirect('/login');
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/attestations', [\App\Http\Controllers\AttestationController::class, 'show'])->name('attestations');
    Route::get('/attestations/{id}', [\App\Http\Controllers\AttestationsMakeController::class, 'show'])
        ->middleware(\App\Http\Middleware\EnsureIsAdmin::class);
    Route::patch('/attestations', [\App\Http\Controllers\AttestationsMakeController::class, 'edit'])
        ->middleware(\App\Http\Middleware\EnsureIsAdmin::class);
    Route::post('/attestations', [\App\Http\Controllers\AttestationController::class, 'create'])
        ->middleware(\App\Http\Middleware\EnsureIsAdmin::class);
    Route::delete('/attestations', [\App\Http\Controllers\AttestationController::class, 'delete'])
        ->middleware(\App\Http\Middleware\EnsureIsAdmin::class);
    Route::put('/attestations', [\App\Http\Controllers\AttestationController::class, 'edit'])
        ->middleware(\App\Http\Middleware\EnsureIsAdmin::class);

    Route::get('/user', [\App\Http\Controllers\UserController::class, 'show'])->name('user')
        ->middleware(\App\Http\Middleware\EnsureIsAdmin::class);
    Route::delete('/user', [\App\Http\Controllers\UserController::class, 'delete'])
        ->middleware(\App\Http\Middleware\EnsureIsAdmin::class);
    Route::put('/user', [\App\Http\Controllers\UserController::class, 'edit'])
        ->middleware(\App\Http\Middleware\EnsureIsAdmin::class);
    Route::post('/user', [\App\Http\Controllers\UserController::class, 'create'])
        ->middleware(\App\Http\Middleware\EnsureIsAdmin::class);
});

require __DIR__ . '/auth.php';
