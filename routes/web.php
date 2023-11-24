<?php

use App\Http\Controllers\AttestationController;
use App\Http\Controllers\AttestationsMakeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MyAttestationsController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\CheckPrivileges;
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

Route::delete('/notifications', [DashboardController::class, 'delete'])->middleware(['auth', 'verified']);

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'show'])->name('dashboard');
    Route::get('/dashboard/data', [DashboardController::class, 'getData']);
    Route::patch('/dashboard/todo', [DashboardController::class, 'checkToDo']);
    Route::delete('/dashboard/todo', [DashboardController::class, 'deleteToDo']);

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/my_attestations', [MyAttestationsController::class, 'show'])->name('my_attestations');

    Route::middleware(CheckPrivileges::class)->group(function() {
        Route::post('/dashboard/todo', [DashboardController::class, 'createToDo'])->name('create_to_do');
        Route::get('/dashboard/users', [DashboardController::class, 'getUsers'])->name('get_users');

        Route::get('/attestations/{id}', [AttestationsMakeController::class, 'show'])->name('show_make_attestation');
        Route::patch('/attestations', [AttestationsMakeController::class, 'make'])->name('make_attestation');
        Route::get('/attestations', [AttestationController::class, 'show'])->name('attestations');
        Route::post('/attestations', [AttestationController::class, 'create'])->name('create_subject');
        Route::post('/attestations/users/upload', [AttestationController::class, 'upload'])->name('upload_user_subject');
        Route::post('/attestations/users/include', [AttestationController::class, 'includeUsersToAttestation']);
        Route::delete('/attestations', [AttestationController::class, 'delete'])->name('delete_subject');
        Route::put('/attestations', [AttestationController::class, 'edit'])->name('edit_subject');

        Route::patch('/attestations/comment', [AttestationController::class, 'updateComment'])->name('update_comment');

        Route::get('/users', [UserController::class, 'show'])->name('user');
        Route::delete('/users', [UserController::class, 'delete'])->name('delete_user');
        Route::put('/users', [UserController::class, 'edit'])->name('edit_user');
        Route::post('/users', [UserController::class, 'create'])->name('create_user');
        Route::post('/users/upload', [UserController::class, 'upload'])->name('create_user_upload');

        Route::post('/notifications', [DashboardController::class, 'send'])->name('send_notification');

        Route::get('roles', [RoleController::class, 'show'])->name('roles');
        Route::patch('roles', [RoleController::class, 'update'])->name('edit_roles');
    });
});

require __DIR__ . '/auth.php';
