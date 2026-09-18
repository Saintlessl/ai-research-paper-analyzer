export type Role = 'researcher' | 'reviewer' | 'admin';
type RoleSource = { role?: string | null; roles?: Array<{ name: string } | string> };
export type PaperState = 'uploaded' | 'queued' | 'processing' | 'analyzed' | 'failed' | 'archived' | 'unknown';
export type Paginated<T> = { data: T[]; current_page?: number; last_page?: number; total?: number; links?: { url: string | null; label: string; active: boolean }[] };

export const asArray = <T>(value?: T[] | Paginated<T> | null): T[] => Array.isArray(value) ? value : value?.data ?? [];
export const normalizeRole = (user: RoleSource): Role | null => {
    const candidate = user.role ?? (typeof user.roles?.[0] === 'string' ? user.roles[0] : user.roles?.[0]?.name);
    const role = candidate?.toLowerCase();
    return role === 'admin' || role === 'reviewer' || role === 'researcher' ? role : null;
};
export const paperStatus = (value?: string | null) => {
    const state = (value ?? 'unknown').toLowerCase() as PaperState;
    const map: Record<PaperState, { label: string; className: string }> = {
        uploaded: { label: 'Uploaded', className: 'status-neutral' }, queued: { label: 'Queued', className: 'status-warning' },
        processing: { label: 'Processing', className: 'status-info' }, analyzed: { label: 'Analyzed', className: 'status-success' },
        failed: { label: 'Failed', className: 'status-danger' }, archived: { label: 'Archived', className: 'status-neutral' },
        unknown: { label: 'Unknown', className: 'status-neutral' },
    };
    return map[state] ?? map.unknown;
};
export const displayDate = (value?: string | null) => value ? new Intl.DateTimeFormat(undefined, { dateStyle: 'medium' }).format(new Date(value)) : '—';
export const displayNumber = (value?: number | null) => value == null ? '—' : new Intl.NumberFormat().format(value);
export const initials = (name?: string) => (name || 'User').split(/\s+/).slice(0, 2).map((part) => part[0]).join('').toUpperCase();
