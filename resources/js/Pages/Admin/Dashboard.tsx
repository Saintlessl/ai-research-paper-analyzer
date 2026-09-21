import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { RefreshCw, Activity, AlertTriangle, CheckCircle2, ListFilter, Users } from 'lucide-react';
import { NeuCard } from '@/Components/ui/NeuCard';
import { NeuButton } from '@/Components/ui/NeuButton';
import { NeuStatusPill, ScoreGauge } from '@/Components/ui/NeuData';
import { NeuIconButton } from '@/Components/ui/NeuNavigation';
import { NeuErrorState } from '@/Components/ui/NeuState';
import { displayNumber, displayDate } from '@/lib/contracts';

export default function Dashboard({ metrics, recentJobs, recentLogs, error }: any) {
  const retryJob = (id: number) => {
    router.post(`/admin/jobs/${id}/retry`);
  };

  if (error) {
    return (
      <AuthenticatedLayout>
        <Head title="Dashboard Error" />
        <div className="flex items-center justify-center min-h-[60vh]">
          <NeuErrorState 
            title="Unable to load dashboard" 
            message={error || "We couldn't retrieve the system metrics at this time. Please try again later."} 
            onRetry={() => router.reload()} 
          />
        </div>
      </AuthenticatedLayout>
    );
  }

  const cards = [
    { label: 'Total Papers', value: metrics.total_papers },
    { label: 'Analyzed', value: metrics.analyzed_papers },
    { label: 'Processing', value: metrics.processing_papers },
    { label: 'Total Users', value: metrics.total_users },
    { label: 'Pending Jobs', value: metrics.pending_jobs },
    { label: 'Failed Jobs', value: metrics.failed_jobs },
  ];

  return (
    <AuthenticatedLayout 
      header={
        <div>
          <h2 className="text-2xl font-bold leading-tight">System Dashboard</h2>
          <p className="text-sm text-neu-muted mt-1 tracking-wide uppercase font-semibold">Administration</p>
        </div>
      }
    >
      <Head title="Admin Dashboard" />

      {/* Stat Cards */}
      <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 mb-8">
        {cards.map((card, idx) => (
          <NeuCard key={idx} padding="md" className="flex flex-col">
            <span className="text-xs font-semibold text-neu-muted uppercase tracking-wider">{card.label}</span>
            <div className="mt-2 flex items-baseline gap-1">
              <span className="text-3xl font-bold tabular-nums text-neu-text">{displayNumber(card.value)}</span>
            </div>
          </NeuCard>
        ))}
      </div>

      {/* Charts / High-level metrics */}
      <div className="grid gap-6 md:grid-cols-2 xl:grid-cols-4 mb-8">
        <NeuCard className="flex flex-col items-center justify-center text-center" padding="lg">
          <ScoreGauge score={metrics.ai_success_rate || 0} label="AI Success Rate" />
        </NeuCard>
        
        <NeuCard className="flex flex-col items-center justify-center text-center" padding="lg">
          <ScoreGauge score={metrics.average_score || 0} label="Avg Paper Score" />
        </NeuCard>
        
        <NeuCard className="xl:col-span-2 p-6 flex flex-col justify-center">
          <h3 className="font-bold mb-2 flex items-center gap-2">
            <Activity className="h-5 w-5 text-neu-accent-text" /> 
            System Health
          </h3>
          <p className="text-sm text-neu-muted mb-4">
            Monitoring AI worker queue and background jobs.
          </p>
          <div className="space-y-3">
            <div className="flex justify-between items-center text-sm">
              <span className="font-semibold">Queue Status</span>
              {metrics.failed_jobs > 0 ? (
                <span className="text-status-failed-text font-bold flex items-center gap-1"><AlertTriangle className="h-4 w-4" /> Attention Required</span>
              ) : (
                <span className="text-status-analyzed-text font-bold flex items-center gap-1"><CheckCircle2 className="h-4 w-4" /> Healthy</span>
              )}
            </div>
            <div className="w-full h-px bg-neu-hairline" />
            <div className="flex justify-between items-center text-sm">
              <span className="font-semibold">Pending Jobs</span>
              <span className="tabular-nums font-bold">{metrics.pending_jobs}</span>
            </div>
          </div>
        </NeuCard>
      </div>

      <div className="grid gap-6 lg:grid-cols-2">
        {/* Recent Jobs */}
        <NeuCard padding="none" className="overflow-hidden">
          <div className="flex justify-between items-center p-5 border-b border-neu-hairline bg-neu-surface">
            <h2 className="font-bold flex items-center gap-2"><ListFilter className="h-5 w-5" /> Recent AI Jobs</h2>
          </div>
          <div className="divide-y divide-neu-hairline">
            {recentJobs.map((job: any) => (
              <div key={job.id} className="flex justify-between items-center p-4 hover:bg-neu-bg transition-colors">
                <div className="min-w-0 flex-1">
                  <p className="font-bold text-sm">Paper #{job.paper_id}</p>
                  <p className="text-xs text-neu-muted mt-1">{job.user?.name || 'System'}</p>
                  {job.error_message && <p className="text-xs text-status-failed-text mt-1 truncate max-w-sm" title={job.error_message}>{job.error_message}</p>}
                </div>
                <div className="flex items-center gap-3 shrink-0">
                  <NeuStatusPill status={job.status} />
                  {job.status === 'FAILED' && (
                    <NeuIconButton 
                      onClick={() => retryJob(job.id)} 
                      icon={<RefreshCw className="h-4 w-4" />} 
                      variant="ghost" 
                      title="Retry Job"
                      aria-label="Retry Job"
                    />
                  )}
                </div>
              </div>
            ))}
            {recentJobs.length === 0 && (
              <div className="p-8 text-center text-neu-muted text-sm">No recent jobs found.</div>
            )}
          </div>
        </NeuCard>
        
        {/* Recent Logs */}
        <NeuCard padding="none" className="overflow-hidden">
          <div className="flex justify-between items-center p-5 border-b border-neu-hairline bg-neu-surface">
            <h2 className="font-bold flex items-center gap-2"><Users className="h-5 w-5" /> Audit Logs</h2>
            <Link href="/admin/logs" className="text-sm font-bold text-neu-accent-text hover:underline">View all</Link>
          </div>
          <div className="divide-y divide-neu-hairline">
            {recentLogs.map((log: any) => (
              <div key={log.id} className="p-4 hover:bg-neu-bg transition-colors text-sm">
                <div className="flex justify-between">
                  <span className="font-bold">{log.action}</span>
                  <span className="text-xs text-neu-muted">{displayDate(log.created_at)}</span>
                </div>
                <p className="text-xs text-neu-text mt-1">
                  <span className="font-semibold">{log.actor?.name || 'System'}</span> &rarr; {log.target_type} #{log.target_id}
                </p>
              </div>
            ))}
            {recentLogs.length === 0 && (
              <div className="p-8 text-center text-neu-muted text-sm">No logs recorded yet.</div>
            )}
          </div>
        </NeuCard>
      </div>
    </AuthenticatedLayout>
  );
}
