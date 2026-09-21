import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import { NeuTable, NeuTableRow, NeuBadge } from '@/Components/ui/NeuData';

export default function AuditLogs({ logs }: any) {
    return (
        <AuthenticatedLayout
            header={
                <div>
                    <h2 className="text-2xl font-bold leading-tight">Audit Logs</h2>
                    <p className="text-sm text-neu-muted mt-1">System-wide activity and security audit trail.</p>
                </div>
            }
        >
            <Head title="Audit Logs" />
            
            <div className="max-w-7xl mx-auto mt-6">
                <NeuTable headers={['Timestamp', 'Actor', 'Action', 'Target', 'Metadata']}>
                    {logs.data.map((log: any) => (
                        <NeuTableRow key={log.id}>
                            <td className="px-6 py-4 text-neu-muted whitespace-nowrap">{new Date(log.created_at).toLocaleString()}</td>
                            <td className="px-6 py-4 font-bold text-neu-text">{log.actor?.name || 'System'}</td>
                            <td className="px-6 py-4">
                                <NeuBadge>{log.action}</NeuBadge>
                            </td>
                            <td className="px-6 py-4 text-neu-muted font-mono text-xs">
                                {log.target_type && String(log.target_type).split('\\').pop()} #{log.target_id}
                            </td>
                            <td className="px-6 py-4">
                                <pre className="text-xs bg-neu-surface neu-pressed p-3 rounded-neu-sm max-w-sm overflow-auto text-neu-text">
                                    {JSON.stringify(log.metadata, null, 2)}
                                </pre>
                            </td>
                        </NeuTableRow>
                    ))}
                </NeuTable>
            </div>
        </AuthenticatedLayout>
    );
}
