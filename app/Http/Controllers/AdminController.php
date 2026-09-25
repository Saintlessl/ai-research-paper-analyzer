<?php

namespace App\Http\Controllers;

use App\Enums\RoleName;
use App\Models\AiJob;
use App\Models\AuditLog;
use App\Models\Paper;
use App\Models\PaperScore;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Jobs\ProcessPaper;

class AdminController extends Controller
{
    public function dashboard(Request $request): Response
    {
        if ($request->query('simulate_error')) {
            return Inertia::render('Admin/Dashboard', [
                'error' => 'Redis cache unavailable: Unable to load system metrics. (Simulated Error)'
            ]);
        }

        $totalJobs = AiJob::count();
        $completedJobs = AiJob::where('status', 'COMPLETED')->count();

        $metrics = [
            'total_papers' => Paper::count(),
            'analyzed_papers' => Paper::where('status', 'ANALYZED')->count(),
            'processing_papers' => Paper::where('status', 'PROCESSING')->count(),
            'total_users' => User::count(),
            'failed_jobs' => AiJob::where('status', 'FAILED')->count(),
            'pending_jobs' => AiJob::whereIn('status', ['PENDING', 'PROCESSING'])->count(),
            'average_score' => round((float) PaperScore::avg('score'), 1),
            'ai_success_rate' => $totalJobs > 0 ? round(($completedJobs / $totalJobs) * 100, 1) : 0,
        ];

        $recentJobs = AiJob::with('user')->latest()->take(10)->get();
        $recentLogs = AuditLog::with('actor')->latest()->take(10)->get();

        return Inertia::render('Admin/Dashboard', [
            'metrics' => $metrics,
            'recentJobs' => $recentJobs,
            'recentLogs' => $recentLogs,
        ]);
    }

    public function retryJob(Request $request, AiJob $job): RedirectResponse
    {
        if ($job->status !== 'FAILED') {
            return back()->withErrors(['job' => 'Only failed jobs can be retried.']);
        }

        $job->update([
            'status' => 'PENDING',
            'error_message' => null,
            'error_code' => null,
            'retry_count' => 0,
        ]);

        ProcessPaper::dispatch($job->id);

        return back()->with('status', 'job-retried');
    }

    public function users(): Response
    {
        return Inertia::render('Admin/Users', [
            'users' => User::with('roles')->latest()->paginate(20),
            'availableRoles' => collect(RoleName::cases())->map(fn ($r) => $r->value)->toArray(),
        ]);
    }

    public function updateUserRole(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'role' => 'required|string|in:' . implode(',', array_map(fn ($r) => $r->value, RoleName::cases())),
        ]);

        $role = \App\Models\Role::where('name', $request->role)->firstOrFail();
        $user->roles()->sync([$role->id]);

        return back()->with('status', 'role-updated');
    }

    public function logs(): Response
    {
        return Inertia::render('Admin/AuditLogs', [
            'logs' => AuditLog::with('actor')->latest()->paginate(50),
        ]);
    }

    public function impersonate(Request $request, User $user): RedirectResponse
    {
        // Don't allow impersonating other super admins
        if ($user->hasRole(RoleName::SuperAdmin)) {
            abort(403, 'Cannot impersonate a Super Admin.');
        }

        $request->session()->put('impersonated_by', $request->user()->id);
        \Illuminate\Support\Facades\Auth::login($user);

        return redirect()->route('dashboard');
    }

    public function leaveImpersonation(Request $request): RedirectResponse
    {
        if (!$request->session()->has('impersonated_by')) {
            abort(403, 'Not currently impersonating.');
        }

        $originalAdminId = $request->session()->pull('impersonated_by');
        \Illuminate\Support\Facades\Auth::loginUsingId($originalAdminId);

        return redirect()->route('admin.users');
    }
}
