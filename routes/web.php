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

Route::get('/dashboard', function (\Illuminate\Http\Request $request) {
    $user = $request->user();
    
    // Simulate error for testing
    if ($request->query('simulate_error')) {
        return Inertia::render('Dashboard', [
            'error' => 'Database connection failed while retrieving research metrics. (Simulated Error)'
        ]);
    }

    // Researchers see their papers
    if ($user->hasRole('researcher')) {
        $metrics = [
            'total_papers' => \App\Models\Paper::where('uploaded_by', $user->id)->count(),
            'analyzed_papers' => \App\Models\Paper::where('uploaded_by', $user->id)->where('status', 'ANALYZED')->count(),
        ];
        $recentPapers = \App\Models\Paper::where('uploaded_by', $user->id)->latest()->take(5)->get();
        return Inertia::render('Dashboard', [
            'metrics' => $metrics,
            'recentPapers' => $recentPapers,
        ]);
    }
    
    // Reviewers see assigned papers
    if ($user->hasRole('reviewer')) {
        $metrics = [
            'assigned_reviews' => \App\Models\Review::where('reviewer_id', $user->id)->count(),
            'pending_reviews' => \App\Models\Review::where('reviewer_id', $user->id)->where('status', 'DRAFT')->count(),
        ];
        $recentPapers = \App\Models\Paper::whereHas('reviews', function($q) use ($user) {
            $q->where('reviewer_id', $user->id);
        })->latest()->take(5)->get();
        return Inertia::render('Dashboard', [
            'metrics' => $metrics,
            'recentPapers' => $recentPapers,
        ]);
    }

    // Admins redirect to admin dashboard
    if ($user->hasRole('admin') || $user->hasRole('super_admin')) {
        return redirect()->route('admin.dashboard');
    }

    return Inertia::render('Dashboard');
})->middleware(['auth', 'role:researcher,reviewer,admin,super_admin'])->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::get('/papers', [PaperController::class, 'index'])->name('papers.index');
    Route::get('/papers/create', [PaperController::class, 'create'])->name('papers.create');
    Route::post('/papers', [PaperController::class, 'store'])->name('papers.store')->middleware('throttle:uploads');
    
    // Comparison
    Route::get('/papers/compare', [\App\Http\Controllers\PaperComparisonController::class, 'create'])->name('papers.compare.create');
    Route::post('/papers/compare', [\App\Http\Controllers\PaperComparisonController::class, 'store'])->name('papers.compare.store')->middleware('throttle:ai_requests');
    Route::get('/papers/compare/{comparison}', [\App\Http\Controllers\PaperComparisonController::class, 'show'])->name('papers.compare.show');
    
    Route::get('/papers/{paper}/edit', [PaperController::class, 'edit'])->name('papers.edit');
    Route::get('/papers/{paper}/download', [PaperController::class, 'download'])->name('papers.download');
    Route::patch('/papers/{paper}', [PaperController::class, 'update'])->name('papers.update');
    Route::delete('/papers/{paper}', [PaperController::class, 'destroy'])->name('papers.destroy');
    Route::get('/papers/{paper}', [PaperController::class, 'show'])->name('papers.show');
    
    // Q&A
    Route::post('/papers/{paper}/questions', [\App\Http\Controllers\PaperQuestionController::class, 'store'])->name('papers.questions.store')->middleware('throttle:ai_requests');

    // Reviews and Assignments
    Route::post('/papers/{paper}/ai-review', [\App\Http\Controllers\ReviewController::class, 'store'])->name('papers.reviews.store')->middleware('throttle:ai_requests');
    Route::patch('/reviews/{review}', [\App\Http\Controllers\ReviewController::class, 'update'])->name('reviews.update');
    Route::post('/papers/{paper}/assign', [\App\Http\Controllers\ReviewerAssignmentController::class, 'store'])->name('papers.assignments.store');
    Route::delete('/papers/{paper}/assign/{reviewer}', [\App\Http\Controllers\ReviewerAssignmentController::class, 'destroy'])->name('papers.assignments.destroy');
    Route::get('/papers/{paper}/recommendations', [\App\Http\Controllers\ReviewerRecommendationController::class, 'show'])->name('papers.recommendations');
    Route::post('/reviews/{review}/comments', [\App\Http\Controllers\ReviewCommentController::class, 'store'])->name('reviews.comments.store');
    Route::get('/papers/{paper}/export', [\App\Http\Controllers\ExportController::class, 'show'])->name('papers.export');
});

Route::middleware(['auth', 'permission:access_admin_panel'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\AdminController::class, 'dashboard'])->name('dashboard');
    Route::post('/jobs/{job}/retry', [\App\Http\Controllers\AdminController::class, 'retryJob'])->name('jobs.retry')->middleware('permission:manage_jobs');
    Route::get('/users', [\App\Http\Controllers\AdminController::class, 'users'])->name('users')->middleware('permission:manage_users');
    Route::patch('/users/{user}/role', [\App\Http\Controllers\AdminController::class, 'updateUserRole'])->name('users.updateRole')->middleware('permission:manage_users');
    Route::get('/logs', [\App\Http\Controllers\AdminController::class, 'logs'])->name('logs')->middleware('permission:view_logs');
    
    // Roles & Permissions UI
    Route::get('/roles-permissions', [\App\Http\Controllers\RolePermissionController::class, 'index'])->name('roles.index')->middleware('permission:manage_role_permissions');
    Route::post('/roles/{role}/permissions', [\App\Http\Controllers\RolePermissionController::class, 'update'])->name('roles.permissions.update')->middleware('permission:manage_role_permissions');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

if (app()->environment('local')) {
    Route::get('/design-system', function () {
        return Inertia\Inertia::render('DesignSystem');
    });
}
