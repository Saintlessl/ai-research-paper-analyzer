import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { NeuCard } from '@/Components/ui/NeuCard';
import { NeuButton } from '@/Components/ui/NeuButton';
import { NeuBadge } from '@/Components/ui/NeuData';

interface Permission {
    id: number;
    name: string;
    description: string;
}

interface Role {
    id: number;
    name: string;
    permissions: Permission[];
}

export default function RolePermissions({ roles, permissions }: { roles: Role[], permissions: Permission[] }) {
    const [processing, setProcessing] = useState<number | null>(null);

    const togglePermission = (role: Role, permissionId: number, hasPermission: boolean) => {
        setProcessing(role.id);
        
        let newPermissions = role.permissions.map(p => p.id);
        
        if (hasPermission) {
            newPermissions = newPermissions.filter(id => id !== permissionId);
        } else {
            newPermissions.push(permissionId);
        }

        router.post(route('admin.roles.permissions.update', role.id), {
            permissions: newPermissions
        }, {
            preserveScroll: true,
            onFinish: () => setProcessing(null)
        });
    };

    return (
        <AuthenticatedLayout
            header={
                <div>
                    <h2 className="text-2xl font-bold leading-tight">Role & Permissions</h2>
                    <p className="text-sm text-neu-muted mt-1">Configure what each role can access dynamically. (Super Admin implicitly bypasses all).</p>
                </div>
            }
        >
            <Head title="Role & Permissions" />

            <div className="space-y-6">
                {roles.map((role) => (
                    <NeuCard key={role.id}>
                            <div className="flex justify-between items-center mb-6">
                                <div className="flex items-center gap-3">
                                    <h3 className="text-lg font-bold capitalize">{role.name} Role</h3>
                                    <NeuBadge className="bg-neu-primary/20 text-neu-primary-text">
                                        {role.permissions.length} Permissions
                                    </NeuBadge>
                                </div>
                                {processing === role.id && (
                                    <span className="text-sm text-neu-muted animate-pulse">Saving changes...</span>
                                )}
                            </div>
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                {permissions.map(permission => {
                                    const hasPermission = role.permissions.some(p => p.id === permission.id);
                                    
                                    return (
                                        <div key={permission.id} 
                                            className="flex items-center justify-between p-4 rounded-xl neu-flat bg-neu-base/50"
                                        >
                                            <div className="flex flex-col pr-4">
                                                <span className="font-semibold text-sm capitalize">{permission.name.replace(/_/g, ' ')}</span>
                                                <span className="text-xs text-neu-muted mt-1">{permission.description}</span>
                                            </div>
                                            
                                            {/* Custom Neumorphic Switch */}
                                            <label className="relative inline-flex items-center cursor-pointer shrink-0">
                                                <input 
                                                    type="checkbox" 
                                                    className="sr-only peer" 
                                                    checked={hasPermission}
                                                    onChange={() => togglePermission(role, permission.id, hasPermission)}
                                                    disabled={processing === role.id}
                                                />
                                                <div className={`w-11 h-6 bg-neu-base neu-pressed rounded-full peer 
                                                    peer-checked:after:translate-x-full peer-checked:after:border-white 
                                                    after:content-[''] after:absolute after:top-[2px] after:left-[2px] 
                                                    after:bg-neu-primary after:border-neu-primary after:border after:rounded-full after:h-5 after:w-5 
                                                    after:transition-all peer-checked:after:bg-emerald-500 peer-checked:after:border-emerald-500
                                                    ${processing === role.id ? 'opacity-50 cursor-not-allowed' : ''}`}>
                                                </div>
                                            </label>
                                        </div>
                                    );
                                })}
                            </div>
                    </NeuCard>
                ))}
            </div>
        </AuthenticatedLayout>
    );
}
