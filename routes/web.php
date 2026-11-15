<?php

use App\Http\Controllers\ArchiveController;
use App\Http\Controllers\ContentHistoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::middleware(['guest'])->group(function () {
    Route::get('/login', [LoginController::class, 'index'])->name('login');
    Route::post('/login', [LoginController::class, 'auth'])->name('login.auth');
});

Route::middleware(['auth'])->group(function () {
    Route::put('/home/update', [HomeController::class, 'update'])->name('home.update');

    Route::prefix('/admin')->name('admin.')->group(function () {
        // Dashboard
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // Users CRUD & History (Admin only)
        Route::middleware(['role:admin'])->group(function () {
            Route::get('/users', [UserController::class, 'index'])->name('users.index');
            Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
            Route::post('/users', [UserController::class, 'store'])->name('users.store');
            Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
            Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
            Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

            // History & Rollback
            Route::get('/history', [ContentHistoryController::class, 'index'])->name('history.index');
            Route::get('/history/{batch}', [ContentHistoryController::class, 'show'])->name('history.show');
            Route::post('/history/{batch}/rollback', [ContentHistoryController::class, 'rollback'])->name('history.rollback');

            // Force delete archive
            Route::delete('/archive/{type}/{id}', [ArchiveController::class, 'forceDelete'])->name('archive.force-delete');
        });

        // Archive (Admin & Editor)
        Route::get('/archive', [ArchiveController::class, 'index'])->name('archive.index');
        Route::post('/archive/{type}/{id}/restore', [ArchiveController::class, 'restore'])->name('archive.restore');

        // Doctors CRUD
        Route::get('/doctors', [DoctorController::class, 'index'])->name('doctors.index');
        Route::get('/doctors/create', [DoctorController::class, 'create'])->name('doctors.create');
        Route::post('/doctors', [DoctorController::class, 'store'])->name('doctors.store');
        Route::put('/doctors/sort', [DoctorController::class, 'sort'])->name('doctors.sort');
        Route::get('/doctors/{doctor}/edit', [DoctorController::class, 'edit'])->name('doctors.edit');
        Route::put('/doctors/{doctor}', [DoctorController::class, 'update'])->name('doctors.update');
        Route::delete('/doctors/{doctor}', [DoctorController::class, 'destroy'])->name('doctors.destroy');

        // Settings / Medsos (edit-only, menggunakan home_texts)
        Route::get('/settings', [MediaController::class, 'index'])->name('settings.index');
        Route::put('/settings', [MediaController::class, 'update'])->name('settings.update');
    });

    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
});
