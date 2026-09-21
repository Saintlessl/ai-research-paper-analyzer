import { Card, PageHeader } from '@/Components/UI';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';

export default function Users({ users, availableRoles }: any) {
    const [editingId, setEditingId] = useState<number | null>(null);
    const [selectedRole, setSelectedRole] = useState('');

    const updateRole = (userId: number) => {
        router.patch(`/admin/users/${userId}/role`, { role: selectedRole }, {
            onSuccess: () => { setEditingId(null); setSelectedRole(''); },
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title="Users Management" />
            <div className="page-wrap">
                <PageHeader eyebrow="Administration" title="Users Management" />
                
                <Card className="mt-6 overflow-hidden">
                    <table className="w-full text-left text-sm">
                        <thead className="bg-slate-50 border-b">
                            <tr>
                                <th className="p-4 font-semibold">Name</th>
                                <th className="p-4 font-semibold">Email</th>
                                <th className="p-4 font-semibold">Roles</th>
                                <th className="p-4 font-semibold">Joined</th>
                                <th className="p-4 font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {users.data.map((user: any) => (
                                <tr key={user.id}>
                                    <td className="p-4 font-medium">{user.name}</td>
                                    <td className="p-4 text-slate-600">{user.email}</td>
                                    <td className="p-4">
                                        <div className="flex gap-1 flex-wrap">
                                            {user.roles?.map((role: any) => (
                                                <span key={role.id} className="status status-info text-[10px]">{role.name}</span>
                                            ))}
                                        </div>
                                    </td>
                                    <td className="p-4 text-slate-500">{new Date(user.created_at).toLocaleDateString()}</td>
                                    <td className="p-4">
                                        {editingId === user.id ? (
                                            <div className="flex items-center gap-2">
                                                <select
                                                    className="field text-xs py-1"
                                                    value={selectedRole}
                                                    onChange={e => setSelectedRole(e.target.value)}
                                                >
                                                    <option value="">Select role...</option>
                                                    {availableRoles?.map((r: string) => (
                                                        <option key={r} value={r}>{r}</option>
                                                    ))}
                                                </select>
                                                <button
                                                    onClick={() => updateRole(user.id)}
                                                    disabled={!selectedRole}
                                                    className="btn-primary text-xs py-1 px-2"
                                                >
                                                    Save
                                                </button>
                                                <button
                                                    onClick={() => setEditingId(null)}
                                                    className="text-xs text-slate-500 hover:text-slate-800"
                                                >
                                                    Cancel
                                                </button>
                                            </div>
                                        ) : (
                                            <button
                                                onClick={() => { setEditingId(user.id); setSelectedRole(user.roles?.[0]?.name || ''); }}
                                                className="text-xs text-teal-600 hover:text-teal-800 font-semibold"
                                            >
                                                Change Role
                                            </button>
                                        )}
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
