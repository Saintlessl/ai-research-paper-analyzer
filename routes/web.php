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
    
    // Comparison
    Route::get('/papers/compare', [\App\Http\Controllers\PaperComparisonController::class, 'create'])->name('papers.compare.create');
    Route::post('/papers/compare', [\App\Http\Controllers\PaperComparisonController::class, 'store'])->name('papers.compare.store');
    Route::get('/papers/compare/{comparison}', [\App\Http\Controllers\PaperComparisonController::class, 'show'])->name('papers.compare.show');
    
    Route::get('/papers/{paper}/edit', [PaperController::class, 'edit'])->name('papers.edit');
    Route::get('/papers/{paper}/download', [PaperController::class, 'download'])->name('papers.download');
    Route::patch('/papers/{paper}', [PaperController::class, 'update'])->name('papers.update');
    Route::delete('/papers/{paper}', [PaperController::class, 'destroy'])->name('papers.destroy');
    Route::get('/papers/{paper}', [PaperController::class, 'show'])->name('papers.show');
    
    // Q&A
    Route::post('/papers/{paper}/questions', [\App\Http\Controllers\PaperQuestionController::class, 'store'])->name('papers.questions.store');

    // Reviews and Assignments
    Route::post('/papers/{paper}/ai-review', [\App\Http\Controllers\ReviewController::class, 'store'])->name('papers.reviews.store');
    Route::patch('/reviews/{review}', [\App\Http\Controllers\ReviewController::class, 'update'])->name('reviews.update');
    Route::post('/papers/{paper}/assign', [\App\Http\Controllers\ReviewerAssignmentController::class, 'store'])->name('papers.assignments.store');
    Route::delete('/papers/{paper}/assign/{reviewer}', [\App\Http\Controllers\ReviewerAssignmentController::class, 'destroy'])->name('papers.assignments.destroy');
});

Route::middleware(['auth', 'verified', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\AdminController::class, 'dashboard'])->name('dashboard');
    Route::post('/jobs/{job}/retry', [\App\Http\Controllers\AdminController::class, 'retryJob'])->name('jobs.retry');
    Route::get('/users', [\App\Http\Controllers\AdminController::class, 'users'])->name('users');
    Route::get('/logs', [\App\Http\Controllers\AdminController::class, 'logs'])->name('logs');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
