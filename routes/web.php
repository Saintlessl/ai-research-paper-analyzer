<?php

use App\Http\Controllers\PaperController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified', 'role:researcher,reviewer,admin'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/papers', [PaperController::class, 'index'])->name('papers.index');
    Route::get('/papers/create', [PaperController::class, 'create'])->name('papers.create');
    Route::post('/papers', [PaperController::class, 'store'])->name('papers.store');
    Route::get('/papers/{paper}/edit', [PaperController::class, 'edit'])->name('papers.edit');
    Route::get('/papers/{paper}/download', [PaperController::class, 'download'])->name('papers.download');
    Route::patch('/papers/{paper}', [PaperController::class, 'update'])->name('papers.update');
    Route::delete('/papers/{paper}', [PaperController::class, 'destroy'])->name('papers.destroy');
    Route::get('/papers/{paper}', [PaperController::class, 'show'])->name('papers.show');
    
    // Q&A
    Route::post('/papers/{paper}/questions', [\App\Http\Controllers\PaperQuestionController::class, 'store'])->name('papers.questions.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
