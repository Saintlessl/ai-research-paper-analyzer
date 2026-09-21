import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import type { Evidence, Finding, Paper, Reference, Review, Score } from '@/types';
import { displayDate, normalizeRole } from '@/lib/contracts';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { Download, ExternalLink, MessageSquareText, Pencil, Send, Trash2, RotateCcw } from 'lucide-react';
import React, { FormEvent, useState, useEffect } from 'react';
import { NeuCard } from '@/Components/ui/NeuCard';
import { NeuTabs } from '@/Components/ui/NeuNavigation';
import { NeuButton } from '@/Components/ui/NeuButton';
import { NeuStatusPill, ScoreGauge } from '@/Components/ui/NeuData';
import { NeuEmptyState, NeuErrorState } from '@/Components/ui/NeuState';
import { NeuTextarea, NeuSelect } from '@/Components/ui/NeuForm';
import { NeuInput } from '@/Components/ui/NeuInput';

const tabs = [
    { id: 'overview', label: 'Overview' },
    { id: 'analysis', label: 'Analysis' },
    { id: 'scores', label: 'Scores' },
    { id: 'findings', label: 'Findings' },
    { id: 'references', label: 'References' },
    { id: 'reviewer', label: 'AI Reviewer' },
    { id: 'qa', label: 'Q&A' },
    { id: 'reviews', label: 'Reviews' }
];

type Props = {
    paper: Paper;
    tab?: string;
    analysis?: Record<string, unknown> | null;
    scores?: Score[];
    findings?: Finding[];
    references?: Reference[];
    aiReview?: Review | null;
    reviews?: Review[];
    reviewers?: Array<{id: number; name: string}>;
    questions?: Array<{id?: number; question: string; answer?: string | null; found?: boolean; evidence?: Evidence[]}>;
    can?: {download?: boolean; update?: boolean; delete?: boolean; review?: boolean; ask?: boolean; retry?: boolean};
};

export default function Show(p: Props) {
    const active = p.tab ?? new URLSearchParams(window.location.search).get('tab') ?? 'overview';
    
    useEffect(() => {
        let interval: NodeJS.Timeout;
        if (p.paper.status === 'UPLOADED' || p.paper.status === 'PROCESSING') {
            interval = setInterval(() => {
                router.reload({ only: ['paper', 'analysis', 'scores', 'findings', 'aiReview', 'reviews'] });
            }, 3000);
        }
        return () => clearInterval(interval);
    }, [p.paper.status]);

    const deletePaper = () => {
        if (window.confirm('Delete this paper and its private PDF? This cannot be undone.'))
            router.delete(`/papers/${p.paper.id}`);
    };

    return (
        <AuthenticatedLayout
            header={
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <div className="flex items-center gap-3 mb-1">
                            <h2 className="text-2xl font-bold leading-tight">{p.paper.title}</h2>
                            <NeuStatusPill status={p.paper.status as any} />
                        </div>
                        <p className="text-sm text-neu-muted max-w-3xl">
                            {[p.paper.authors?.map(a=>a.name).join(', '), p.paper.journal, p.paper.publication_year].filter(Boolean).join(' · ') || 'Publication metadata not provided'}
                        </p>
                    </div>
                    
                    <div className="flex flex-wrap gap-2">
                        {p.can?.retry === true && p.paper.failed_job_id && (
                            <NeuButton onClick={() => router.post(`/admin/jobs/${p.paper.failed_job_id}/retry`)} variant="secondary">
                                <RotateCcw className="h-4 w-4 mr-2" /> Retry AI
                            </NeuButton>
                        )}
                        {p.can?.download === true && (
                            <a href={`/papers/${p.paper.id}/export`} className="inline-flex">
                                <NeuButton variant="secondary">📄 Export</NeuButton>
                            </a>
                        )}
                        {p.can?.download === true && (
                            <a href={`/papers/${p.paper.id}/download`} className="inline-flex">
                                <NeuButton variant="secondary">
                                    <Download className="h-4 w-4 mr-2" /> PDF
                                </NeuButton>
                            </a>
                        )}
                        {p.can?.update === true && (
                            <Link href={`/papers/${p.paper.id}/edit`}>
                                <NeuButton variant="secondary">
                                    <Pencil className="h-4 w-4 mr-2" /> Edit
                                </NeuButton>
                            </Link>
                        )}
                        {p.can?.delete === true && (
                            <NeuButton type="button" onClick={deletePaper} variant="secondary" className="text-status-failed-text hover:text-status-failed-text">
                                <Trash2 className="h-4 w-4 mr-2" /> Delete
                            </NeuButton>
                        )}
                    </div>
                </div>
            }
        >
            <Head title={p.paper.title} />
            
            <div className="max-w-7xl mx-auto mt-6">
                <NeuTabs tabs={tabs} activeId={active} onChange={(id) => router.get(`/papers/${p.paper.id}?tab=${id}`)} className="mb-6" />
                
                {active === 'overview' && <Overview {...p} />}
                {active === 'analysis' && <Analysis {...p} />}
                {active === 'scores' && <Scores {...p} />}
                {active === 'findings' && <Findings {...p} />}
                {active === 'references' && <References {...p} />}
                {active === 'reviewer' && <ReviewView review={p.aiReview} status={p.paper.status as string} />}
                {active === 'qa' && <Qa {...p} />}
                {active === 'reviews' && <Reviews {...p} />}
            </div>
        </AuthenticatedLayout>
    );
}

function Overview({ paper, analysis }: Props) {
    return (
        <div className="grid gap-6 lg:grid-cols-[1.5fr_.5fr]">
            <NeuCard padding="lg">
                <h2 className="text-lg font-bold border-b border-neu-hairline pb-2 mb-4">Abstract</h2>
                <p className="whitespace-pre-line text-sm leading-7 text-neu-text">
                    {paper.abstract || 'No abstract was provided.'}
                </p>
            </NeuCard>
            <NeuCard padding="lg">
                <h2 className="text-lg font-bold border-b border-neu-hairline pb-2 mb-4">Metadata</h2>
                <dl className="space-y-4 text-sm">
                    <Meta k="DOI" v={paper.doi} />
                    <Meta k="Journal" v={paper.journal} />
                    <Meta k="Year" v={paper.publication_year} />
                    <Meta k="Uploaded" v={displayDate(paper.created_at)} />
                    <Meta k="Domain" v={String(analysis?.research_domain ?? '—')} />
                </dl>
            </NeuCard>
        </div>
    );
}

function Meta({ k, v }: { k: string; v: unknown }) {
    return (
        <div>
            <dt className="text-xs font-semibold uppercase tracking-wide text-neu-muted mb-1">{k}</dt>
            <dd className="break-words font-medium text-neu-text">{v == null || v === '' ? '—' : String(v)}</dd>
        </div>
    );
}

function Analysis({ paper, analysis, can }: Props) {
    if (paper.status === 'FAILED') {
        return (
            <NeuErrorState 
                title="AI Analysis Failed" 
                message={paper.error_message || "The AI encountered an error while analyzing this paper. The original text might still be extracted."} 
                onRetry={can?.retry && paper.failed_job_id ? () => router.post(`/admin/jobs/${paper.failed_job_id}/retry`) : undefined} 
            />
        );
    }
    
    if (paper.status === 'PROCESSING') {
        return <NeuEmptyState title="Analysis in Progress" description="The AI is currently processing this document. Please check back shortly." />;
    }

    if (!analysis || Object.keys(analysis).length === 0) {
        return <NeuEmptyState title="No Analysis Available" description="No analysis data has been generated for this paper yet." />;
    }
    
    const entries = Object.entries(analysis).filter(([, v]) => v != null);
    
    return (
        <div className="grid gap-4 md:grid-cols-2">
            {entries.map(([k, v]) => (
                <NeuCard key={k} padding="lg">
                    <h3 className="font-bold capitalize border-b border-neu-hairline pb-2 mb-4">
                        {k.replaceAll('_', ' ')}
                    </h3>
                    <div className="text-sm leading-6 text-neu-text">
                        {render(v)}
                    </div>
                </NeuCard>
            ))}
        </div>
    );
}

const render = (v: unknown): React.ReactNode => {
    if (Array.isArray(v)) {
        return (
            <ul className="list-disc space-y-1 pl-5 text-neu-muted">
                {v.map((x, i) => (
                    <li key={i} className="text-neu-text">
                        {typeof x === 'object' ? JSON.stringify(x) : String(x)}
                    </li>
                ))}
            </ul>
        );
    }
    if (typeof v === 'object' && v !== null) {
        return (
            <dl className="space-y-3">
                {Object.entries(v as Record<string, unknown>).map(([k, x]) => (
                    <div key={k}>
                        <dt className="font-semibold capitalize text-neu-muted text-xs mb-1">
                            {k.replaceAll('_', ' ')}
                        </dt>
                        <dd className="text-neu-text">
                            {Array.isArray(x) ? x.join(', ') : String(x ?? '—')}
                        </dd>
                    </div>
                ))}
            </dl>
        );
    }
    return String(v);
};

function Scores({ paper, scores = [] }: Props) {
    if (paper.status === 'FAILED') {
        return <NeuErrorState title="Scoring Failed" message="The AI failed to generate scores for this document." />;
    }
    if (paper.status === 'PROCESSING') {
        return <NeuEmptyState title="Scoring in Progress" description="Scores will be available once analysis is complete." />;
    }
    if (scores.length === 0) {
        return <NeuEmptyState title="No Scores Available" description="No scoring data has been generated." />;
    }

    return (
        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            {scores.map((s, i) => (
                <NeuCard key={s.id ?? i} padding="lg" className="flex flex-col">
                    <h3 className="font-bold capitalize mb-4 text-center">
                        {s.criterion.replaceAll('_', ' ')}
                    </h3>
                    <div className="flex justify-center mb-6">
                        <ScoreGauge score={s.score} />
                    </div>
                    <p className="text-sm leading-6 text-neu-muted mt-auto">
                        {s.reason || 'No scoring rationale returned.'}
                    </p>
                </NeuCard>
            ))}
        </div>
    );
}

function Findings({ paper, findings = [] }: Props) {
    if (paper.status === 'FAILED') return <NeuErrorState title="Failed" message="Could not extract findings." />;
    if (paper.status === 'PROCESSING') return <NeuEmptyState title="Processing" description="Extracting findings..." />;
    if (findings.length === 0) return <NeuEmptyState title="No Findings" description="No significant findings were identified." />;

    return (
        <div className="grid gap-4 md:grid-cols-2">
            {findings.map((f, i) => (
                <NeuCard key={f.id ?? i} padding="lg">
                    <div className="flex flex-wrap items-center gap-2 mb-3">
                        <span className={`px-2 py-1 rounded text-xs font-bold uppercase ${
                            ['high', 'critical'].includes(f.severity?.toLowerCase() ?? '') 
                            ? 'bg-status-failed-fill/10 text-status-failed-text' 
                            : 'bg-status-processing-fill/10 text-status-processing-text'
                        }`}>
                            {f.severity || 'Unrated'}
                        </span>
                        <span className="text-xs font-bold uppercase text-neu-muted">
                            {f.category}
                        </span>
                    </div>
                    <h3 className="font-bold text-neu-text">{f.finding || f.title}</h3>
                    <p className="mt-2 text-sm leading-6 text-neu-muted">{f.explanation}</p>
                    <EvidenceList rows={f.evidence} />
                </NeuCard>
            ))}
        </div>
    );
}

function References({ paper, references = [] }: Props) {
    if (paper.status === 'FAILED') return <NeuErrorState title="Failed" message="Could not extract references." />;
    if (paper.status === 'PROCESSING') return <NeuEmptyState title="Processing" description="Extracting references..." />;
    if (references.length === 0) return <NeuEmptyState title="No References" description="No references were found." />;

    return (
        <NeuCard padding="none" className="divide-y divide-neu-hairline">
            {references.map((r, i) => (
                <div className="p-5" key={r.id ?? i}>
                    <p className="text-sm leading-6 text-neu-text font-medium">
                        {r.citation || [r.authors, r.title, r.year].filter(Boolean).join('. ')}
                    </p>
                    {r.cited_in_text != null && (
                        <p className="mt-2 text-xs font-semibold uppercase text-neu-muted">
                            {r.cited_in_text ? '✓ Observed in text' : '✗ Not observed in text'}
                        </p>
                    )}
                </div>
            ))}
        </NeuCard>
    );
}

function ReviewView({ review, status }: { review?: Review | null, status: string }) {
    if (status === 'FAILED') return <NeuErrorState title="Failed" message="Could not generate AI review." />;
    if (status === 'PROCESSING') return <NeuEmptyState title="Processing" description="AI Review is being generated..." />;
    if (!review) return <NeuEmptyState title="No AI Review" description="No AI Review has been generated for this paper." />;

    return (
        <div className="grid gap-6 lg:grid-cols-3">
            <div className="lg:col-span-3">
                <NeuCard padding="lg">
                    <div className="flex flex-wrap justify-between items-center gap-3 mb-4">
                        <h2 className="text-xl font-bold">Reviewer Report</h2>
                        <span className="px-3 py-1 rounded-neu-pill bg-neu-surface neu-pressed text-sm font-bold uppercase text-neu-accent-text">
                            {review.recommendation?.replaceAll('_', ' ') || 'No recommendation'}
                        </span>
                    </div>
                    <p className="text-sm leading-7 text-neu-text">{review.summary}</p>
                </NeuCard>
            </div>
            
            {[
                ['Strengths', review.strengths],
                ['Major Concerns', review.major_concerns],
                ['Minor Concerns', review.minor_concerns]
            ].map(([title, items]) => (
                <NeuCard key={String(title)} padding="lg">
                    <h3 className="font-bold border-b border-neu-hairline pb-2 mb-4">{String(title)}</h3>
                    {Array.isArray(items) && items.length > 0 ? (
                        <ul className="list-disc space-y-2 pl-5 text-sm text-neu-text">
                            {items.map((x, i) => <li key={i}>{x}</li>)}
                        </ul>
                    ) : (
                        <p className="text-sm text-neu-muted italic">None returned.</p>
                    )}
                </NeuCard>
            ))}
        </div>
    );
}

function Qa(p: Props) {
    const f = useForm({ question: '' });
    
    const submit = (e: FormEvent) => {
        e.preventDefault();
        f.post(`/papers/${p.paper.id}/questions`, { onSuccess: () => f.reset() });
    };

    return (
        <div className="grid gap-6 lg:grid-cols-[.4fr_1.6fr] items-start">
            <NeuCard padding="lg" className="sticky top-24">
                <form onSubmit={submit}>
                    <label htmlFor="question" className="font-bold block mb-2">Ask about this paper</label>
                    <p className="text-sm text-neu-muted mb-4">Answers are grounded only in the selected paper.</p>
                    
                    <NeuTextarea 
                        id="question" 
                        required 
                        className="mb-4"
                        value={f.data.question} 
                        onChange={e => f.setData('question', e.target.value)} 
                        placeholder="What methodology does the paper use?"
                    />
                    
                    <NeuButton type="submit" variant="primary" className="w-full" disabled={f.processing || !p.can?.ask}>
                        <Send className="h-4 w-4 mr-2" /> Ask AI
                    </NeuButton>
                    
                    {f.errors.question && <p className="mt-2 text-sm text-status-failed-text">{f.errors.question}</p>}
                </form>
            </NeuCard>

            <div className="space-y-4">
                {p.questions?.length ? (
                    p.questions.map((q, i) => (
                        <NeuCard key={q.id ?? i} padding="lg">
                            <h3 className="font-bold text-lg mb-4">{q.question}</h3>
                            <div className="bg-neu-surface neu-pressed p-4 rounded-neu-md text-sm leading-7 text-neu-text">
                                {q.answer || (
                                    <span className="flex items-center text-neu-muted">
                                        <div className="animate-spin mr-2 w-4 h-4 border-2 border-neu-accent-fill border-t-transparent rounded-full" />
                                        Awaiting answer...
                                    </span>
                                )}
                            </div>
                            <EvidenceList rows={q.evidence} />
                        </NeuCard>
                    ))
                ) : (
                    <NeuEmptyState title="No Questions" description="Ask a question on the left to get started." />
                )}
            </div>
        </div>
    );
}

function Reviews(p: Props) {
    return (
        <div className="space-y-6">
            {p.reviewers && p.reviewers.length > 0 && (
                <AssignReviewer paperId={p.paper.id} reviewers={p.reviewers} />
            )}
            
            {p.reviews?.length ? (
                <div className="grid gap-4 lg:grid-cols-2">
                    {p.reviews.map(r => (
                        <ReviewCard key={r.id} review={r} paperId={p.paper.id} />
                    ))}
                </div>
            ) : (
                <NeuEmptyState title="No Human Reviews" description="No human reviews have been submitted for this paper." />
            )}
        </div>
    );
}

function AssignReviewer({ paperId, reviewers }: { paperId: number; reviewers: Array<{id: number; name: string}> }) {
    const f = useForm({ reviewer_id: '' });
    const [recs, setRecs] = useState<Array<{reviewer_id: number; reason: string; confidence_score: number}> | null>(null);
    const [loadingRecs, setLoadingRecs] = useState(false);
    
    const submit = (e: FormEvent) => {
        e.preventDefault();
        f.post(`/papers/${paperId}/assign`, { onSuccess: () => { f.reset(); setRecs(null); } });
    };

    const fetchRecs = async () => {
        setLoadingRecs(true);
        try {
            const res = await fetch(`/papers/${paperId}/recommendations`);
            const data = await res.json();
            if (data.recommendations) setRecs(data.recommendations);
        } catch (err) {
            console.error(err);
        }
        setLoadingRecs(false);
    };

    return (
        <NeuCard padding="lg" className="border-2 border-neu-accent-fill/20">
            <h3 className="font-bold mb-4">Assign Human Reviewer</h3>
            <form onSubmit={submit} className="flex flex-wrap gap-4 items-end">
                <div className="flex-1 min-w-[250px]">
                    <NeuSelect required value={f.data.reviewer_id} onChange={e => f.setData('reviewer_id', e.target.value)}>
                        <option value="">Select a reviewer...</option>
                        {reviewers.map(r => <option key={r.id} value={r.id}>{r.name}</option>)}
                    </NeuSelect>
                </div>
                <div className="flex gap-2 w-full sm:w-auto">
                    <NeuButton type="button" onClick={fetchRecs} disabled={loadingRecs} variant="secondary" className="flex-1 sm:flex-none">
                        ✨ AI Recommend
                    </NeuButton>
                    <NeuButton type="submit" variant="primary" disabled={f.processing || !f.data.reviewer_id} className="flex-1 sm:flex-none">
                        Assign
                    </NeuButton>
                </div>
            </form>

            {recs && recs.length > 0 && (
                <div className="mt-6 grid gap-4 md:grid-cols-3">
                    {recs.map(r => {
                        const rev = reviewers.find(x => x.id === r.reviewer_id);
                        return (
                            <div key={r.reviewer_id} className="p-4 rounded-neu-sm bg-neu-surface neu-pressed border border-neu-accent-fill/30">
                                <h4 className="font-bold text-neu-text">{rev?.name || 'Unknown'}</h4>
                                <p className="mt-2 text-xs leading-5 text-neu-muted">{r.reason}</p>
                                <button type="button" onClick={() => f.setData('reviewer_id', String(r.reviewer_id))} className="mt-3 text-xs font-bold text-neu-accent-text hover:underline">
                                    Select this reviewer
                                </button>
                            </div>
                        );
                    })}
                </div>
            )}
            {recs && recs.length === 0 && <p className="mt-4 text-sm text-neu-muted font-medium">No recommendations found.</p>}
        </NeuCard>
    );
}

function ReviewCard({ review, paperId }: { review: Review; paperId: number }) {
    const isDraft = review.status === 'DRAFT';
    const f = useForm({ summary: review.summary || '', recommendation: review.recommendation || 'ACCEPT' });
    const cf = useForm({ body: '', page: '' });
    
    const submit = (e: FormEvent) => {
        e.preventDefault();
        f.patch(`/reviews/${review.id}`);
    };
    
    const addComment = (e: FormEvent) => {
        e.preventDefault();
        cf.post(`/reviews/${review.id}/comments`, { onSuccess: () => cf.reset() });
    };

    if (isDraft) {
        return (
            <NeuCard padding="lg" className="border-2 border-neu-accent-fill">
                <h3 className="font-bold text-lg mb-4 text-neu-accent-text">Submit Your Review</h3>
                <form onSubmit={submit} className="space-y-4">
                    <div>
                        <label className="text-sm font-semibold mb-2 block">Recommendation</label>
                        <NeuSelect required value={f.data.recommendation} onChange={e => f.setData('recommendation', e.target.value)}>
                            <option value="ACCEPT">Accept</option>
                            <option value="MINOR_REVISION">Minor Revision</option>
                            <option value="MAJOR_REVISION">Major Revision</option>
                            <option value="REJECT">Reject</option>
                        </NeuSelect>
                    </div>
                    <div>
                        <label className="text-sm font-semibold mb-2 block">Summary</label>
                        <NeuTextarea required className="min-h-[100px]" value={f.data.summary} onChange={e => f.setData('summary', e.target.value)} placeholder="Provide your review summary..."/>
                    </div>
                    <NeuButton type="submit" variant="primary" className="w-full" disabled={f.processing}>
                        Submit Review
                    </NeuButton>
                </form>
            </NeuCard>
        );
    }

    return (
        <NeuCard padding="lg" className="flex flex-col">
            <div className="flex justify-between items-start mb-4">
                <h3 className="font-bold text-lg">{review.reviewer?.name || 'Reviewer'}</h3>
                <span className="px-2 py-1 rounded text-xs font-bold uppercase bg-neu-surface neu-pressed text-neu-muted">
                    {review.recommendation || review.status}
                </span>
            </div>
            
            <p className="text-sm leading-6 text-neu-text mb-6">
                {review.summary || 'No summary provided.'}
            </p>
            
            <div className="mt-auto pt-4 border-t border-neu-hairline">
                <h4 className="text-xs font-bold uppercase text-neu-muted mb-3 flex items-center">
                    <MessageSquareText className="w-3 h-3 mr-1" /> Comments
                </h4>
                
                {review.comments && review.comments.length > 0 && (
                    <div className="space-y-3 mb-4">
                        {review.comments.map((c: any, i: number) => (
                            <div key={c.id ?? i} className="text-sm p-3 bg-neu-surface neu-pressed rounded-neu-sm">
                                <div className="flex justify-between mb-1">
                                    <strong className="text-neu-text">{c.user?.name || 'User'}</strong>
                                    {c.page && <span className="text-xs text-neu-muted">p.{c.page}</span>}
                                </div>
                                <p className="text-neu-text">{c.body}</p>
                            </div>
                        ))}
                    </div>
                )}
                
                <form onSubmit={addComment} className="flex gap-2">
                    <NeuInput className="flex-1 text-sm h-9" placeholder="Add comment..." value={cf.data.body} onChange={(e: any) => cf.setData('body', e.target.value)} required />
                    <NeuInput className="w-16 text-sm h-9" placeholder="Page" type="number" min="1" value={cf.data.page} onChange={(e: any) => cf.setData('page', e.target.value)} />
                    <NeuButton type="submit" variant="secondary" className="h-9 px-3" disabled={cf.processing}>Send</NeuButton>
                </form>
            </div>
        </NeuCard>
    );
}

function EvidenceList({ rows = [] }: { rows?: Evidence[] }) {
    if (!rows.length) return null;
    return (
        <div className="mt-4 border-l-2 border-neu-accent-fill pl-4">
            {rows.map((e, i) => (
                <p key={i} className="text-xs leading-5 text-neu-muted mb-2 last:mb-0">
                    <strong className="text-neu-text font-semibold mr-1">
                        {e.section || 'Evidence'}{e.page ? ` (p.${e.page})` : ''}:
                    </strong>
                    {e.excerpt ? `“${e.excerpt}”` : ''}
                </p>
            ))}
        </div>
    );
}
