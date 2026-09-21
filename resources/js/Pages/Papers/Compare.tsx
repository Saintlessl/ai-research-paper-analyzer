import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import type { Paper } from '@/types';
import { Head, router } from '@inertiajs/react';
import { GitCompareArrows } from 'lucide-react';
import { FormEvent, useState } from 'react';
import { NeuCard } from '@/Components/ui/NeuCard';
import { NeuButton } from '@/Components/ui/NeuButton';
import { NeuSelect } from '@/Components/ui/NeuForm';
import { NeuStatusPill } from '@/Components/ui/NeuData';
import { NeuEmptyState } from '@/Components/ui/NeuState';

type Comparison = {
    dimensions?: Array<{
        dimension: string;
        paper_a?: unknown;
        paper_b?: unknown;
        conclusion?: string;
        evidence_a?: string[];
        evidence_b?: string[];
    }>;
    conclusion?: string;
    reasoning?: string;
};

export default function Compare({ 
    papers = [], 
    selected = {}, 
    comparison, 
    paperA, 
    paperB 
}: { 
    papers?: Paper[]; 
    selected?: { paper_a?: number; paper_b?: number }; 
    comparison?: Comparison | null; 
    paperA?: Paper; 
    paperB?: Paper; 
}) {
    const [a, setA] = useState(String(selected.paper_a ?? ''));
    const [b, setB] = useState(String(selected.paper_b ?? ''));
    
    const submit = (e: FormEvent) => {
        e.preventDefault();
        router.post('/papers/compare', { paper_a_id: a, paper_b_id: b });
    };
    
    const A = paperA ?? papers.find(x => String(x.id) === a);
    const B = paperB ?? papers.find(x => String(x.id) === b);

    return (
        <AuthenticatedLayout
            header={
                <div>
                    <h2 className="text-2xl font-bold leading-tight">Compare Papers</h2>
                    <p className="text-sm text-neu-muted mt-1">Review methodology, findings, and evidence side by side.</p>
                </div>
            }
        >
            <Head title="Compare papers" />
            
            <div className="max-w-7xl mx-auto mt-6">
                <NeuCard padding="lg">
                    <form onSubmit={submit} className="flex flex-col md:flex-row items-end gap-4">
                        <div className="flex-1 w-full">
                            <label className="block text-sm font-semibold mb-2 text-neu-text">Paper A</label>
                            <NeuSelect required value={a} onChange={e => setA(e.target.value)}>
                                <option value="">Choose a paper...</option>
                                {papers.map(p => <option value={p.id} key={p.id}>{p.title}</option>)}
                            </NeuSelect>
                        </div>
                        
                        <div className="hidden md:flex mb-2 text-neu-muted">
                            <GitCompareArrows className="w-6 h-6" />
                        </div>
                        
                        <div className="flex-1 w-full">
                            <label className="block text-sm font-semibold mb-2 text-neu-text">Paper B</label>
                            <NeuSelect required value={b} onChange={e => setB(e.target.value)}>
                                <option value="">Choose a paper...</option>
                                {papers.map(p => <option value={p.id} key={p.id}>{p.title}</option>)}
                            </NeuSelect>
                        </div>
                        
                        <NeuButton type="submit" variant="primary" className="w-full md:w-auto" disabled={!a || !b || a === b}>
                            Compare
                        </NeuButton>
                    </form>
                    
                    {a && b && a === b && (
                        <p className="mt-4 text-sm font-semibold text-status-failed-text text-center bg-status-failed-fill/10 p-2 rounded-neu-sm">
                            Please select two different papers to compare.
                        </p>
                    )}
                </NeuCard>

                {A && B && (
                    <div className="mt-6 grid gap-6 md:grid-cols-2">
                        <PaperHead paper={A} label="Paper A" />
                        <PaperHead paper={B} label="Paper B" />
                    </div>
                )}

                {comparison?.conclusion && (
                    <NeuCard padding="lg" className="mt-6 border-2 border-neu-accent-fill/30 bg-neu-surface neu-pressed">
                        <h2 className="font-bold text-lg text-neu-accent-text mb-2">Overall Conclusion</h2>
                        <p className="text-sm leading-6 text-neu-text font-medium">{comparison.conclusion}</p>
                        {comparison.reasoning && (
                            <p className="mt-3 text-sm text-neu-muted italic border-l-2 border-neu-accent-fill/20 pl-3">
                                {comparison.reasoning}
                            </p>
                        )}
                    </NeuCard>
                )}

                {comparison?.dimensions?.length ? (
                    <div className="mt-6 space-y-6">
                        {comparison.dimensions.map((r, i) => (
                            <NeuCard key={i} padding="none" className="overflow-hidden">
                                <div className="border-b border-neu-hairline bg-neu-surface neu-pressed px-6 py-4">
                                    <h2 className="font-bold text-neu-text capitalize">
                                        {(r.dimension || 'Comparison').replaceAll('_', ' ')}
                                    </h2>
                                </div>
                                <div className="grid md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-neu-hairline">
                                    <CompareCell value={r.paper_a} evidence={r.evidence_a} />
                                    <CompareCell value={r.paper_b} evidence={r.evidence_b} />
                                </div>
                                {r.conclusion && (
                                    <div className="border-t border-neu-hairline bg-neu-surface neu-flat px-6 py-4">
                                        <p className="text-sm text-neu-text">
                                            <strong className="text-neu-accent-text mr-2">Comparative assessment:</strong> 
                                            {r.conclusion}
                                        </p>
                                    </div>
                                )}
                            </NeuCard>
                        ))}
                    </div>
                ) : (
                    <div className="mt-6">
                        <NeuEmptyState 
                            title="Select two analyzed papers" 
                            description="A structured comparison will appear here after the backend returns it." 
                        />
                    </div>
                )}
            </div>
        </AuthenticatedLayout>
    );
}

function PaperHead({ paper, label }: { paper: Paper, label: string }) {
    return (
        <NeuCard padding="lg" className="flex flex-col">
            <div className="text-xs font-bold uppercase text-neu-muted mb-2 tracking-wide">{label}</div>
            <div className="flex items-start justify-between gap-4 mb-3">
                <h2 className="font-bold text-neu-text line-clamp-2" title={paper.title}>{paper.title}</h2>
                <div className="shrink-0">
                    <NeuStatusPill status={paper.status as any} />
                </div>
            </div>
            <p className="mt-auto text-sm text-neu-muted line-clamp-1">
                {paper.authors?.map(a => a.name).join(', ') || 'Authors not provided'}
            </p>
        </NeuCard>
    );
}

function CompareCell({ value, evidence = [] }: { value: unknown; evidence?: string[] }) {
    return (
        <div className="p-6 bg-neu-surface">
            <p className="whitespace-pre-wrap text-sm leading-6 text-neu-text">
                {value == null ? 'Not available' : typeof value === 'object' ? JSON.stringify(value, null, 2) : String(value)}
            </p>
            {evidence && evidence.length > 0 && (
                <div className="mt-4 pt-4 border-t border-neu-hairline">
                    <h4 className="text-xs font-bold uppercase text-neu-muted mb-2">Evidence</h4>
                    <ul className="list-disc pl-4 text-xs leading-5 text-neu-muted space-y-1">
                        {evidence.map((x, i) => <li key={i}>{x}</li>)}
                    </ul>
                </div>
            )}
        </div>
    );
}
