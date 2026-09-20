<?php

namespace App\Http\Controllers;

use App\Models\AiJob;
use App\Models\AuditLog;
use App\Models\Paper;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Facades\DB;
use App\Jobs\ProcessPaper;

class AdminController extends Controller
{
    public function dashboard(): Response
    {
        $metrics = [
            'total_papers' => Paper::count(),
            'total_users' => User::count(),
            'failed_jobs' => AiJob::where('status', 'FAILED')->count(),
            'pending_jobs' => AiJob::whereIn('status', ['PENDING', 'PROCESSING'])->count(),
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
        ]);
    }

    public function logs(): Response
    {
        return Inertia::render('Admin/AuditLogs', [
            'logs' => AuditLog::with('actor')->latest()->paginate(50),
        ]);
    }
}
