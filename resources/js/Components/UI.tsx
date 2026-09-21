import { Link } from '@inertiajs/react';
import { AlertCircle, CheckCircle2, Clock3, FileQuestion, LoaderCircle } from 'lucide-react';
import type { ReactNode } from 'react';
import { paperStatus } from '@/lib/contracts';

/** @deprecated Use Neu components instead */
export function PageHeader({ eyebrow, title, description, actions }: { eyebrow?: string; title: string; description?: string; actions?: ReactNode }) {
 return <div className="mb-6 flex flex-col gap-4 border-b border-slate-200 pb-6 sm:flex-row sm:items-end sm:justify-between"><div className="min-w-0">{eyebrow && <p className="eyebrow mb-2">{eyebrow}</p>}<h1 className="text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">{title}</h1>{description && <p className="mt-2 max-w-3xl text-sm leading-6 text-slate-600">{description}</p>}</div>{actions && <div className="flex shrink-0 flex-wrap gap-2">{actions}</div>}</div>;
}

/** @deprecated Use NeuCard instead */
export function Card({ children, className = '' }: { children: ReactNode; className?: string }) { return <section className={`surface ${className}`}>{children}</section>; }

/** @deprecated Use NeuStatusPill instead */
export function StatusBadge({ status }: { status?: string | null }) { const meta = paperStatus(status); return <span className={`status ${meta.className}`}><span className="h-1.5 w-1.5 rounded-full bg-current" />{meta.label}</span>; }

/** @deprecated Use NeuEmptyState instead */
export function EmptyState({ title, description, action }: { title: string; description: string; action?: ReactNode }) { return <div className="flex min-h-64 flex-col items-center justify-center px-6 py-12 text-center"><span className="mb-4 rounded-2xl bg-slate-100 p-3 text-slate-500"><FileQuestion className="h-6 w-6" /></span><h3 className="font-semibold text-slate-900">{title}</h3><p className="mt-1 max-w-md text-sm leading-6 text-slate-500">{description}</p>{action && <div className="mt-5">{action}</div>}</div>; }

/** @deprecated Use NeuStatusPill or NeuErrorState instead */
export function ProcessingState({ status, error }: { status?: string | null; error?: string | null }) { const state = status?.toLowerCase(); if (state === 'failed') return <div className="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800"><div className="flex gap-3"><AlertCircle className="mt-0.5 h-5 w-5 shrink-0"/><div><strong>Analysis could not be completed.</strong><p className="mt-1">{error || 'The paper remains available. Try again later or contact an administrator.'}</p></div></div></div>; if (state !== 'analyzed') return <div className="rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-800"><div className="flex gap-3">{state === 'processing' ? <LoaderCircle className="mt-0.5 h-5 w-5 shrink-0 animate-spin"/> : <Clock3 className="mt-0.5 h-5 w-5 shrink-0"/>}<div><strong>{state === 'processing' ? 'Analysis in progress' : 'Waiting for analysis'}</strong><p className="mt-1">Results will appear only after server-side processing completes.</p></div></div></div>; return <div className="flex items-center gap-2 text-sm font-medium text-emerald-700"><CheckCircle2 className="h-5 w-5"/>Analysis complete</div>; }

/** @deprecated Use NeuTabs instead */
export function Tabs({ items, active, baseUrl }: { items: Array<{ id: string; label: string }>; active: string; baseUrl: string }) { return <nav aria-label="Paper sections" className="mb-6 overflow-x-auto border-b border-slate-200"><div className="flex min-w-max gap-1">{items.map(i => <Link key={i.id} href={`${baseUrl}?tab=${i.id}`} preserveScroll className={`border-b-2 px-3 py-3 text-sm font-semibold ${active === i.id ? 'border-teal-700 text-teal-800' : 'border-transparent text-slate-500 hover:text-slate-900'}`}>{i.label}</Link>)}</div></nav>; }

/** @deprecated Use NeuSkeleton instead */
export function Skeleton({ rows = 4 }: { rows?: number }) { return <div aria-label="Loading" className="animate-pulse space-y-3 p-6">{Array.from({ length: rows }).map((_, i) => <div key={i} className="h-4 rounded bg-slate-200" style={{ width: `${92 - i * 7}%` }}/>)}</div>; }

/** @deprecated Use NeuCard instead */
export function Metric({ label, value, detail }: { label: string; value: ReactNode; detail?: string }) { return <Card className="p-5"><p className="text-sm font-medium text-slate-500">{label}</p><p className="mt-2 text-3xl font-bold tracking-tight text-slate-950">{value}</p>{detail && <p className="mt-2 text-xs text-slate-500">{detail}</p>}</Card>; }
