import React from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, usePage, router } from '@inertiajs/react';
import { asArray, displayDate, displayNumber, normalizeRole, type Paginated } from '@/lib/contracts';
import type { AuthenticatedPageProps, Paper } from '@/types';
import { BookOpen, Upload, CircleAlert, Clock3, FileCheck2, ArrowRight } from 'lucide-react';
import { NeuCard } from '@/Components/ui/NeuCard';
import { NeuButton } from '@/Components/ui/NeuButton';
import { NeuStatusPill } from '@/Components/ui/NeuData';
import { NeuEmptyState, NeuSkeleton, NeuErrorState } from '@/Components/ui/NeuState';
import { BarChart, Bar, XAxis, YAxis, Tooltip, ResponsiveContainer, PieChart, Pie, Cell, LineChart, Line, CartesianGrid } from 'recharts';

type Props = AuthenticatedPageProps<{ 
  error?: string;
  metrics?: Partial<Record<'total_papers'|'analyzed_papers'|'processing_papers'|'failed_papers'|'average_score'|'assigned_papers'|'pending_reviews',number|null>>; 
  recentPapers?: Paper[]|Paginated<Paper>; 
  domainBreakdown?: Array<{label?:string;domain?:string;count:number}>; 
  monthlyUploads?: Array<{label?:string;month?:string;count:number}> 
}>;

const CustomTooltip = ({ active, payload, label }: any) => {
  if (active && payload && payload.length) {
    return (
      <div className="bg-neu-surface border border-neu-hairline rounded-neu-sm p-3 shadow-lg neu-raised-md text-neu-text z-tooltip">
        <p className="font-bold text-sm mb-1">{label}</p>
        {payload.map((entry: any, index: number) => (
          <p key={index} className="text-sm">
            <span className="font-semibold">{entry.name}: </span>
            {entry.value}
          </p>
        ))}
      </div>
    );
  }
  return null;
};

export default function Dashboard() {
  const p = usePage<Props>().props;
  const role = normalizeRole(p.auth.user);
  const metrics = p.metrics ?? {};
  const papers = asArray(p.recentPapers);
  
  if (p.error) {
    return (
      <AuthenticatedLayout>
        <Head title="Dashboard Error" />
        <div className="flex items-center justify-center min-h-[60vh]">
          <NeuErrorState 
            title="Unable to load dashboard" 
            message={p.error || "We couldn't retrieve your research data at this time. Please try again later."} 
            onRetry={() => router.reload()} 
          />
        </div>
      </AuthenticatedLayout>
    );
  }

  const cards = role === 'reviewer' 
    ? [
        { label: 'Assigned Papers', value: metrics.assigned_papers },
        { label: 'Pending Reviews', value: metrics.pending_reviews },
        { label: 'Completed Analyses', value: metrics.analyzed_papers }
      ]
    : [
        { label: 'Total Papers', value: metrics.total_papers },
        { label: 'Analyzed', value: metrics.analyzed_papers },
        { label: 'Processing', value: metrics.processing_papers },
        { label: 'Failed', value: metrics.failed_papers },
        { label: 'Average Score', value: metrics.average_score, suffix: '/100' }
      ];

  const domainData = p.domainBreakdown?.map(d => ({ name: d.label ?? d.domain ?? 'Other', value: d.count })) || [];
  const monthlyData = p.monthlyUploads?.map(d => ({ name: d.label ?? d.month ?? '', papers: d.count })) || [];

  return (
    <AuthenticatedLayout 
      header={
        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
          <div>
            <h2 className="text-2xl font-bold leading-tight">Welcome back, {p.auth.user.name.split(' ')[0]}</h2>
            <p className="text-sm text-neu-muted mt-1 tracking-wide uppercase font-semibold">{role} Dashboard</p>
          </div>
          {p.auth.capabilities.upload_papers && (
            <Link href="/papers/create">
              <NeuButton variant="primary" className="w-full sm:w-auto">
                <Upload className="h-4 w-4 mr-2" /> Upload Paper
              </NeuButton>
            </Link>
          )}
        </div>
      }
    >
      <Head title="Dashboard" />

      {/* Stat Cards */}
      <div className={`grid gap-4 ${cards.length > 3 ? 'sm:grid-cols-2 lg:grid-cols-5' : 'sm:grid-cols-3'} mb-8`}>
        {cards.map((card, idx) => (
          <NeuCard key={idx} padding="md" className="flex flex-col">
            <span className="text-sm font-semibold text-neu-muted">{card.label}</span>
            <div className="mt-2 flex items-baseline gap-1">
              <span className="text-3xl font-bold tabular-nums text-neu-text">{displayNumber(card.value)}</span>
              {card.suffix && <span className="text-sm font-bold text-neu-muted">{card.suffix}</span>}
            </div>
          </NeuCard>
        ))}
      </div>

      <div className="grid gap-6 lg:grid-cols-2 xl:grid-cols-3 mb-8">
        {/* Chart: Research Domains */}
        <NeuCard padding="md" className="flex flex-col xl:col-span-1">
          <h3 className="font-bold mb-4">Research Domains</h3>
          <div className="h-64 w-full neu-pressed rounded-neu-md p-4 flex-1">
            {domainData.length > 0 ? (
              <ResponsiveContainer width="100%" height="100%">
                <PieChart>
                  <Pie
                    data={domainData}
                    cx="50%"
                    cy="50%"
                    innerRadius={60}
                    outerRadius={80}
                    paddingAngle={5}
                    dataKey="value"
                    style={{ stroke: 'none' }}
                  >
                    {domainData.map((entry, index) => (
                      <Cell key={`cell-${index}`} style={{ fill: index % 2 === 0 ? 'var(--neu-accent-fill)' : 'var(--color-severity-medium-fill)' }} />
                    ))}
                  </Pie>
                  <Tooltip content={<CustomTooltip />} />
                </PieChart>
              </ResponsiveContainer>
            ) : (
              <div className="h-full flex items-center justify-center text-neu-muted text-sm">No domain data</div>
            )}
          </div>
        </NeuCard>

        {/* Chart: Monthly Uploads */}
        <NeuCard padding="md" className="flex flex-col lg:col-span-1 xl:col-span-2">
          <h3 className="font-bold mb-4">Papers Uploaded per Month</h3>
          <div className="h-64 w-full neu-pressed rounded-neu-md p-4 flex-1">
            {monthlyData.length > 0 ? (
              <ResponsiveContainer width="100%" height="100%">
                <LineChart data={monthlyData}>
                  <CartesianGrid strokeDasharray="3 3" style={{ stroke: 'var(--neu-hairline)' }} vertical={false} />
                  <XAxis dataKey="name" style={{ stroke: 'var(--neu-muted)' }} fontSize={12} tickLine={false} axisLine={false} />
                  <YAxis style={{ stroke: 'var(--neu-muted)' }} fontSize={12} tickLine={false} axisLine={false} />
                  <Tooltip content={<CustomTooltip />} />
                  <Line type="monotone" dataKey="papers" name="Papers" style={{ stroke: 'var(--neu-accent-fill)' }} strokeWidth={3} dot={{ style: { fill: 'var(--neu-accent-fill)' }, strokeWidth: 2, r: 4 }} activeDot={{ r: 6 }} />
                </LineChart>
              </ResponsiveContainer>
            ) : (
              <div className="h-full flex items-center justify-center text-neu-muted text-sm">No upload data</div>
            )}
          </div>
        </NeuCard>
      </div>

      {/* Recent Papers */}
      <NeuCard padding="none" className="overflow-hidden">
        <div className="flex items-center justify-between p-5 border-b border-neu-hairline">
          <h3 className="font-bold">Recent Papers</h3>
          <Link href="/papers" className="text-sm font-bold text-neu-accent-text hover:underline">View all</Link>
        </div>
        
        {p.metrics === undefined ? (
          // Loading state
          <div className="p-5 space-y-4">
            <NeuSkeleton className="h-16 w-full rounded-neu-sm" />
            <NeuSkeleton className="h-16 w-full rounded-neu-sm" />
            <NeuSkeleton className="h-16 w-full rounded-neu-sm" />
          </div>
        ) : papers.length > 0 ? (
          <div className="p-5">
            {/* NeuTable approach: Container is raised (the card), rows are flat */}
            <div className="flex flex-col gap-3">
              {papers.slice(0, 6).map(x => (
                <Link href={`/papers/${x.id}`} key={x.id} className="flex items-center gap-4 p-4 rounded-neu-md border border-neu-hairline bg-neu-surface hover:bg-neu-bg transition-colors neu-flat group">
                  <div className="flex-1 min-w-0">
                    <p className="font-bold truncate text-sm">{x.title}</p>
                    <p className="text-xs text-neu-muted mt-1">{displayDate(x.created_at)}</p>
                  </div>
                  <div className="flex items-center gap-4 shrink-0">
                    <NeuStatusPill status={x.status as any} />
                    <ArrowRight className="h-5 w-5 text-neu-muted group-hover:text-neu-accent-text transition-colors hidden sm:block" />
                  </div>
                </Link>
              ))}
            </div>
          </div>
        ) : (
          <NeuEmptyState 
            title="No papers yet" 
            description="Upload a paper to get started with AI analysis."
          />
        )}
      </NeuCard>
    </AuthenticatedLayout>
  );
}
