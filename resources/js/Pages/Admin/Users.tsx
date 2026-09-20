import { Card, PageHeader } from '@/Components/UI';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link } from '@inertiajs/react';

export default function Users({ users }: any) {
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
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
