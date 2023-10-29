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

Route::delete('/notifications', [\App\Http\Controllers\DashboardController::class, 'delete'])->middleware(['auth', 'verified']);

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\DashboardController::class, 'show'])->name('dashboard');
    Route::patch('/dashboard/todo', [\App\Http\Controllers\DashboardController::class, 'check_to_do']);
    Route::delete('/dashboard/todo', [\App\Http\Controllers\DashboardController::class, 'delete_to_do']);

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/my_attestations', [\App\Http\Controllers\MyAttestationsController::class, 'show'])->name('my_attestations');

    Route::middleware(\App\Http\Middleware\CheckPrivileges::class)->group(function() {
        Route::post('/dashboard/todo', [\App\Http\Controllers\DashboardController::class, 'create_to_do'])->name('create_to_do');

        Route::get('/attestations/{id}', [\App\Http\Controllers\AttestationsMakeController::class, 'show'])->name('show_make_attestation');
        Route::patch('/attestations', [\App\Http\Controllers\AttestationsMakeController::class, 'make'])->name('make_attestation');
        Route::get('/attestations', [\App\Http\Controllers\AttestationController::class, 'show'])->name('attestations');
        Route::post('/attestations', [\App\Http\Controllers\AttestationController::class, 'create'])->name('create_subject');
        Route::post('/attestations/users/upload', [\App\Http\Controllers\AttestationController::class, 'upload'])->name('upload_user_subject');
        Route::post('/attestations/users/include', [\App\Http\Controllers\AttestationController::class, 'include_users_to_attestation']);
        Route::delete('/attestations', [\App\Http\Controllers\AttestationController::class, 'delete'])->name('delete_subject');
        Route::put('/attestations', [\App\Http\Controllers\AttestationController::class, 'edit'])->name('edit_subject');

        Route::patch('/attestations/comment', [\App\Http\Controllers\AttestationController::class, 'updateComment'])->name('update_comment');

        Route::get('/users', [\App\Http\Controllers\UserController::class, 'show'])->name('user');
        Route::delete('/users', [\App\Http\Controllers\UserController::class, 'delete'])->name('delete_user');
        Route::put('/users', [\App\Http\Controllers\UserController::class, 'edit'])->name('edit_user');
        Route::post('/users', [\App\Http\Controllers\UserController::class, 'create'])->name('create_user');
        Route::post('/users/upload', [\App\Http\Controllers\UserController::class, 'upload'])->name('create_user_upload');

        Route::post('/notifications', [\App\Http\Controllers\DashboardController::class, 'send'])->name('send_notification');

        Route::get('roles', [\App\Http\Controllers\RoleController::class, 'show'])->name('roles');
        Route::patch('roles', [\App\Http\Controllers\RoleController::class, 'update'])->name('edit_roles');
    });
});

require __DIR__ . '/auth.php';
