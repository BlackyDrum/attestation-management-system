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

Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'show'])->middleware(['auth', 'verified'])->name('dashboard');
Route::delete('/dashboard', [\App\Http\Controllers\DashboardController::class, 'delete'])->middleware(['auth', 'verified']);

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/attestations', [\App\Http\Controllers\AttestationController::class, 'show'])->name('attestations');

    Route::middleware(\App\Http\Middleware\EnsureIsAdmin::class)->group(function() {
        Route::get('/attestations/{id}', [\App\Http\Controllers\AttestationsMakeController::class, 'show']);
        Route::patch('/attestations', [\App\Http\Controllers\AttestationsMakeController::class, 'make']);
        Route::post('/attestations', [\App\Http\Controllers\AttestationController::class, 'create']);
        Route::delete('/attestations', [\App\Http\Controllers\AttestationController::class, 'delete']);
        Route::put('/attestations', [\App\Http\Controllers\AttestationController::class, 'edit']);

        Route::get('/users', [\App\Http\Controllers\UserController::class, 'show'])->name('user');
        Route::delete('/users', [\App\Http\Controllers\UserController::class, 'delete']);
        Route::put('/users', [\App\Http\Controllers\UserController::class, 'edit']);
        Route::post('/users', [\App\Http\Controllers\UserController::class, 'create']);
        Route::post('/users/upload', [\App\Http\Controllers\UserController::class, 'upload']);
    });
});

require __DIR__ . '/auth.php';
