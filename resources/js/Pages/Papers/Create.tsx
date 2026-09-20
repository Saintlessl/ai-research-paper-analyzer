import InputError from '@/Components/InputError';
import { Card, PageHeader } from '@/Components/UI';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FileUp, ShieldCheck, Trash2, UserPlus } from 'lucide-react';
import { FormEvent, ReactNode } from 'react';

type AuthorInput = {
    name: string;
    email: string;
    affiliation: string;
    orcid: string;
};

type FormData = {
    title: string;
    abstract: string;
    publication_year: string;
    journal: string;
    doi: string;
    keywords: string;
    authors: AuthorInput[];
    file: File | null;
};

const blankAuthor = (): AuthorInput => ({ name: '', email: '', affiliation: '', orcid: '' });

export default function Create({
    maxUploadMegabytes,
    maxPublicationYear,
}: {
    maxUploadMegabytes: number;
    maxPublicationYear: number;
}) {
    const form = useForm<FormData>({
        title: '',
        abstract: '',
        publication_year: '',
        journal: '',
        doi: '',
        keywords: '',
        authors: [blankAuthor()],
        file: null,
    });
    const errors = form.errors as Record<string, string | undefined>;

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.post('/papers', { forceFormData: true });
    };

    const setAuthor = (index: number, field: keyof AuthorInput, value: string) => {
        form.setData(
            'authors',
            form.data.authors.map((author, authorIndex) =>
                authorIndex === index ? { ...author, [field]: value } : author,
            ),
        );
    };

    return (
        <AuthenticatedLayout>
            <Head title="Upload paper" />
            <div className="page-wrap max-w-5xl">
                <PageHeader
                    eyebrow="Research library"
                    title="Upload a research paper"
                    description="Store a private PDF with normalized academic metadata and an ordered author list."
                />
                <form onSubmit={submit} className="grid gap-6 lg:grid-cols-[1.4fr_.6fr]">
                    <div className="space-y-6">
                        <Card className="p-6">
                            <div className="grid gap-5 sm:grid-cols-2">
                                <Field label="Title" error={errors.title} wide>
                                    <input required className="field" value={form.data.title} onChange={(event) => form.setData('title', event.target.value)} />
                                </Field>
                                <Field label="Abstract" error={errors.abstract} wide>
                                    <textarea className="field min-h-28" value={form.data.abstract} onChange={(event) => form.setData('abstract', event.target.value)} />
                                </Field>
                                <Field label="Publication year" error={errors.publication_year}>
                                    <input type="number" min="1600" max={maxPublicationYear} className="field" value={form.data.publication_year} onChange={(event) => form.setData('publication_year', event.target.value)} />
                                </Field>
                                <Field label="Journal" error={errors.journal}>
                                    <input className="field" value={form.data.journal} onChange={(event) => form.setData('journal', event.target.value)} />
                                </Field>
                                <Field label="DOI" error={errors.doi}>
                                    <input className="field" value={form.data.doi} onChange={(event) => form.setData('doi', event.target.value)} placeholder="10.xxxx/…" />
                                </Field>
                                <Field label="Keywords" error={errors.keywords}>
                                    <input className="field" value={form.data.keywords} onChange={(event) => form.setData('keywords', event.target.value)} placeholder="Comma separated" />
                                </Field>
                            </div>
                        </Card>

                        <Card className="p-6">
                            <div className="flex flex-wrap items-center justify-between gap-3">
                                <div><h2 className="font-semibold">Authors</h2><p className="mt-1 text-sm text-slate-500">Order is preserved from top to bottom.</p></div>
                                <button type="button" className="btn-secondary" disabled={form.data.authors.length >= 100} onClick={() => form.setData('authors', [...form.data.authors, blankAuthor()])}>
                                    <UserPlus className="h-4 w-4" /> Add author
                                </button>
                            </div>
                            <InputError message={errors.authors} className="mt-3" />
                            <div className="mt-5 space-y-4">
                                {form.data.authors.map((author, index) => (
                                    <div key={index} className="rounded-xl border border-slate-200 p-4">
                                        <div className="mb-4 flex items-center justify-between">
                                            <h3 className="text-sm font-semibold">Author {index + 1}</h3>
                                            <button type="button" className="rounded-lg p-2 text-slate-500 hover:bg-rose-50 hover:text-rose-700" aria-label={`Remove author ${index + 1}`} onClick={() => form.setData('authors', form.data.authors.filter((_, authorIndex) => authorIndex !== index))}>
                                                <Trash2 className="h-4 w-4" />
                                            </button>
                                        </div>
                                        <div className="grid gap-4 sm:grid-cols-2">
                                            {(['name', 'email', 'affiliation', 'orcid'] as const).map((field) => (
                                                <Field key={field} label={field === 'orcid' ? 'ORCID' : field[0].toUpperCase() + field.slice(1)} error={errors[`authors.${index}.${field}`]}>
                                                    <input required={field === 'name'} type={field === 'email' ? 'email' : 'text'} className="field" value={author[field]} onChange={(event) => setAuthor(index, field, event.target.value)} />
                                                </Field>
                                            ))}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </Card>
                    </div>

                    <div className="space-y-5">
                        <Card className="p-6">
                            <label className="block text-sm font-semibold" htmlFor="file">PDF document</label>
                            <label htmlFor="file" className="mt-3 flex min-h-44 cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-slate-300 p-5 text-center hover:border-teal-500 hover:bg-teal-50">
                                <FileUp className="h-8 w-8 text-teal-700" />
                                <span className="mt-3 text-sm font-semibold">{form.data.file?.name || 'Choose a PDF'}</span>
                                <span className="mt-1 text-xs text-slate-500">PDF only · Maximum {maxUploadMegabytes} MB</span>
                            </label>
                            <input id="file" required type="file" accept="application/pdf,.pdf" className="sr-only" onChange={(event) => form.setData('file', event.target.files?.[0] ?? null)} />
                            <InputError message={errors.file} className="mt-2" />
                            {form.progress && <div className="mt-4"><div className="h-2 overflow-hidden rounded-full bg-slate-100"><div className="h-full bg-teal-600" style={{ width: `${form.progress.percentage}%` }} /></div><p className="mt-1 text-xs text-slate-500">Uploading {form.progress.percentage}%</p></div>}
                        </Card>
                        <div className="flex gap-2 rounded-xl bg-slate-100 p-4 text-xs leading-5 text-slate-600"><ShieldCheck className="h-5 w-5 shrink-0 text-teal-700" />The server validates file contents and stores the PDF outside the public web root.</div>
                        <div className="flex gap-3"><Link href="/papers" className="btn-secondary flex-1">Cancel</Link><button disabled={form.processing} className="btn-primary flex-1">{form.processing ? 'Uploading…' : 'Upload paper'}</button></div>
                    </div>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}

function Field({ label, error, wide, children }: { label: string; error?: string; wide?: boolean; children: ReactNode }) {
    return <label className={wide ? 'sm:col-span-2' : ''}><span className="mb-2 block text-sm font-semibold">{label}</span>{children}<InputError message={error} className="mt-2" /></label>;
}