import { Card, PageHeader } from '@/Components/UI';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

export default function AuditLogs({ logs }: any) {
    return (
        <AuthenticatedLayout>
            <Head title="Audit Logs" />
            <div className="page-wrap">
                <PageHeader eyebrow="Administration" title="Audit Logs" />
                
                <Card className="mt-6 overflow-hidden">
                    <table className="w-full text-left text-sm">
                        <thead className="bg-slate-50 border-b">
                            <tr>
                                <th className="p-4 font-semibold">Timestamp</th>
                                <th className="p-4 font-semibold">Actor</th>
                                <th className="p-4 font-semibold">Action</th>
                                <th className="p-4 font-semibold">Target</th>
                                <th className="p-4 font-semibold">Metadata</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {logs.data.map((log: any) => (
                                <tr key={log.id}>
                                    <td className="p-4 text-slate-500 whitespace-nowrap">{new Date(log.created_at).toLocaleString()}</td>
                                    <td className="p-4 font-medium">{log.actor?.name || 'System'}</td>
                                    <td className="p-4 text-slate-700 font-mono text-xs">{log.action}</td>
                                    <td className="p-4 text-slate-600">
                                        {log.target_type && String(log.target_type).split('\\').pop()} #{log.target_id}
                                    </td>
                                    <td className="p-4">
                                        <pre className="text-[10px] bg-slate-50 p-2 rounded max-w-xs overflow-auto">
                                            {JSON.stringify(log.metadata, null, 2)}
                                        </pre>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
