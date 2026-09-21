import { Card, PageHeader, StatusBadge } from '@/Components/UI';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { RefreshCw } from 'lucide-react';

export default function Dashboard({ metrics, recentJobs, recentLogs }: any) {
    const retryJob = (id: number) => {
        router.post(`/admin/jobs/${id}/retry`);
    };

    return (
        <AuthenticatedLayout>
            <Head title="Admin Dashboard" />
            <div className="page-wrap">
                <PageHeader eyebrow="Administration" title="System Dashboard" />
                
                <div className="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Card className="p-5 bg-teal-50">
                        <h3 className="text-sm font-semibold text-teal-900">Total Papers</h3>
                        <p className="mt-2 text-3xl font-bold text-teal-700">{metrics.total_papers}</p>
                    </Card>
                    <Card className="p-5 bg-green-50">
                        <h3 className="text-sm font-semibold text-green-900">Analyzed</h3>
                        <p className="mt-2 text-3xl font-bold text-green-700">{metrics.analyzed_papers}</p>
                    </Card>
                    <Card className="p-5 bg-yellow-50">
                        <h3 className="text-sm font-semibold text-yellow-900">Processing</h3>
                        <p className="mt-2 text-3xl font-bold text-yellow-700">{metrics.processing_papers}</p>
                    </Card>
                    <Card className="p-5 bg-blue-50">
                        <h3 className="text-sm font-semibold text-blue-900">Total Users</h3>
                        <p className="mt-2 text-3xl font-bold text-blue-700">{metrics.total_users}</p>
                    </Card>
                    <Card className="p-5 bg-rose-50">
                        <h3 className="text-sm font-semibold text-rose-900">Failed Jobs</h3>
                        <p className="mt-2 text-3xl font-bold text-rose-700">{metrics.failed_jobs}</p>
                    </Card>
                    <Card className="p-5 bg-amber-50">
                        <h3 className="text-sm font-semibold text-amber-900">Pending Jobs</h3>
                        <p className="mt-2 text-3xl font-bold text-amber-700">{metrics.pending_jobs}</p>
                    </Card>
                    <Card className="p-5 bg-purple-50">
                        <h3 className="text-sm font-semibold text-purple-900">Average Score</h3>
                        <p className="mt-2 text-3xl font-bold text-purple-700">{metrics.average_score ?? '—'}</p>
                    </Card>
                    <Card className="p-5 bg-indigo-50">
                        <h3 className="text-sm font-semibold text-indigo-900">AI Success Rate</h3>
                        <p className="mt-2 text-3xl font-bold text-indigo-700">{metrics.ai_success_rate}%</p>
                    </Card>
                </div>

                <div className="mt-8 grid gap-8 lg:grid-cols-2">
                    <Card className="p-5">
                        <div className="flex justify-between items-center mb-4">
                            <h2 className="font-semibold">Recent AI Jobs</h2>
                        </div>
                        <div className="space-y-4">
                            {recentJobs.map((job: any) => (
                                <div key={job.id} className="flex justify-between items-center p-3 border rounded">
                                    <div>
                                        <p className="font-medium text-sm">Paper #{job.paper_id}</p>
                                        <p className="text-xs text-slate-500">{job.user?.name || 'System'}</p>
                                        {job.error_message && <p className="text-xs text-rose-600 mt-1">{job.error_message}</p>}
                                    </div>
                                    <div className="flex items-center gap-3">
                                        <StatusBadge status={job.status} />
                                        {job.status === 'FAILED' && (
                                            <button onClick={() => retryJob(job.id)} className="text-teal-600 hover:text-teal-800" title="Retry Job">
                                                <RefreshCw className="h-4 w-4" />
                                            </button>
                                        )}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </Card>
                    
                    <Card className="p-5">
                        <div className="flex justify-between items-center mb-4">
                            <h2 className="font-semibold">Recent Audit Logs</h2>
                            <Link href="/admin/logs" className="text-sm text-teal-600">View all</Link>
                        </div>
                        <div className="space-y-3">
                            {recentLogs.map((log: any) => (
                                <div key={log.id} className="p-3 border-b last:border-0 text-sm">
                                    <div className="flex justify-between">
                                        <span className="font-medium">{log.action}</span>
                                        <span className="text-xs text-slate-500">{new Date(log.created_at).toLocaleString()}</span>
                                    </div>
                                    <p className="text-xs text-slate-600 mt-1">
                                        {log.actor?.name} &rarr; {log.target_type} #{log.target_id}
                                    </p>
                                </div>
                            ))}
                        </div>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
