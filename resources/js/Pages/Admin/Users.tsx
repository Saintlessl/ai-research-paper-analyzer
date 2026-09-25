import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { NeuCard } from '@/Components/ui/NeuCard';
import { NeuTable, NeuTableRow, NeuBadge } from '@/Components/ui/NeuData';
import { NeuButton } from '@/Components/ui/NeuButton';
import { NeuSelect } from '@/Components/ui/NeuForm';

export default function Users({ users, availableRoles }: any) {
    const [editingId, setEditingId] = useState<number | null>(null);
    const [selectedRole, setSelectedRole] = useState('');

    const updateRole = (userId: number) => {
        router.patch(`/admin/users/${userId}/role`, { role: selectedRole }, {
            onSuccess: () => { setEditingId(null); setSelectedRole(''); },
        });
    };

    return (
        <AuthenticatedLayout
            header={
                <div>
                    <h2 className="text-2xl font-bold leading-tight">Users Management</h2>
                    <p className="text-sm text-neu-muted mt-1">Manage system access and assign roles to users.</p>
                </div>
            }
        >
            <Head title="Users Management" />
            
            <div className="max-w-7xl mx-auto mt-6">
                <NeuTable headers={['Name', 'Email', 'Roles', 'Joined', 'Actions']}>
                    {users.data.map((user: any) => (
                        <NeuTableRow key={user.id}>
                            <td className="px-6 py-4 font-bold text-neu-text">{user.name}</td>
                            <td className="px-6 py-4 text-neu-muted">{user.email}</td>
                            <td className="px-6 py-4">
                                <div className="flex gap-2 flex-wrap">
                                    {user.roles?.map((role: any) => (
                                        <NeuBadge key={role.id}>{role.name}</NeuBadge>
                                    ))}
                                    {(!user.roles || user.roles.length === 0) && (
                                        <span className="text-xs text-neu-muted italic">No role</span>
                                    )}
                                </div>
                            </td>
                            <td className="px-6 py-4 text-neu-muted">{new Date(user.created_at).toLocaleDateString()}</td>
                            <td className="px-6 py-4">
                                {editingId === user.id ? (
                                    <div className="flex items-center gap-2">
                                        <NeuSelect
                                            className="py-1 text-xs w-32"
                                            value={selectedRole}
                                            onChange={e => setSelectedRole(e.target.value)}
                                        >
                                            <option value="">Select role...</option>
                                            {availableRoles?.map((r: string) => (
                                                <option key={r} value={r}>{r}</option>
                                            ))}
                                        </NeuSelect>
                                        <NeuButton
                                            onClick={() => updateRole(user.id)}
                                            disabled={!selectedRole}
                                            variant="primary"
                                            size="sm"
                                        >
                                            Save
                                        </NeuButton>
                                        <NeuButton
                                            onClick={() => setEditingId(null)}
                                            variant="secondary"
                                            size="sm"
                                        >
                                            Cancel
                                        </NeuButton>
                                    </div>
                                ) : (
                                    <div className="flex items-center gap-3">
                                        <button
                                            onClick={() => { setEditingId(user.id); setSelectedRole(user.roles?.[0]?.name || ''); }}
                                            className="text-xs font-bold text-neu-accent-text hover:underline"
                                        >
                                            Change Role
                                        </button>
                                        <button
                                            onClick={() => router.post(`/admin/users/${user.id}/impersonate`)}
                                            className="text-xs font-bold text-neu-primary hover:underline"
                                        >
                                            Impersonate
                                        </button>
                                    </div>
                                )}
                            </td>
                        </NeuTableRow>
                    ))}
                </NeuTable>
                
                {/* Basic pagination controls could go here */}
            </div>
        </AuthenticatedLayout>
    );
}
