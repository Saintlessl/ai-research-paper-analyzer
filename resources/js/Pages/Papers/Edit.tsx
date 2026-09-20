import InputError from '@/Components/InputError';
import { Card, PageHeader } from '@/Components/UI';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, useForm } from '@inertiajs/react';
import { Trash2, UserPlus } from 'lucide-react';
import { FormEvent } from 'react';

type AuthorInput = {
    name: string;
    email: string;
    affiliation: string;
    orcid: string;
};

type EditablePaper = {
    id: number;
    title: string;
    abstract?: string | null;
    publication_year?: number | null;
    journal?: string | null;
    doi?: string | null;
    keywords?: string[] | null;
    authors?: Array<Partial<AuthorInput>>;
};

type FormData = {
    title: string;
    abstract: string;
    publication_year: string;
    journal: string;
    doi: string;
    keywords: string;
    authors: AuthorInput[];
};

const blankAuthor = (): AuthorInput => ({
    name: '',
    email: '',
    affiliation: '',
    orcid: '',
});

export default function Edit({
    paper,
    maxPublicationYear,
}: {
    paper: EditablePaper;
    maxPublicationYear: number;
}) {
    const form = useForm<FormData>({
        title: paper.title,
        abstract: paper.abstract ?? '',
        publication_year: paper.publication_year?.toString() ?? '',
        journal: paper.journal ?? '',
        doi: paper.doi ?? '',
        keywords: paper.keywords?.join(', ') ?? '',
        authors: paper.authors?.length
            ? paper.authors.map((author) => ({
                  name: author.name ?? '',
                  email: author.email ?? '',
                  affiliation: author.affiliation ?? '',
                  orcid: author.orcid ?? '',
              }))
            : [blankAuthor()],
    });
    const errors = form.errors as Record<string, string | undefined>;

    const submit = (event: FormEvent) => {
        event.preventDefault();
        form.patch(`/papers/${paper.id}`, { preserveScroll: true });
    };

    const setAuthor = (index: number, field: keyof AuthorInput, value: string) => {
        form.setData(
            'authors',
            form.data.authors.map((author, authorIndex) =>
                authorIndex === index ? { ...author, [field]: value } : author,
            ),
        );
    };

    const removeAuthor = (index: number) => {
        form.setData(
            'authors',
            form.data.authors.filter((_, authorIndex) => authorIndex !== index),
        );
    };

    return (
        <AuthenticatedLayout>
            <Head title={`Edit ${paper.title}`} />
            <div className="page-wrap max-w-5xl">
                <PageHeader
                    eyebrow="Paper metadata"
                    title="Edit paper"
                    description="Update academic metadata and the ordered author list. The private PDF is never exposed or replaced here."
                />
                <form onSubmit={submit} className="space-y-6">
                    <Card className="p-6">
                        <div className="grid gap-5 sm:grid-cols-2">
                            <Field label="Title" error={errors.title} wide>
                                <input
                                    required
                                    className="field"
                                    value={form.data.title}
                                    onChange={(event) => form.setData('title', event.target.value)}
                                />
                            </Field>
                            <Field label="Abstract" error={errors.abstract} wide>
                                <textarea
                                    className="field min-h-32"
                                    value={form.data.abstract}
                                    onChange={(event) => form.setData('abstract', event.target.value)}
                                />
                            </Field>
                            <Field label="Publication year" error={errors.publication_year}>
                                <input
                                    type="number"
                                    min="1600"
                                    max={maxPublicationYear}
                                    className="field"
                                    value={form.data.publication_year}
                                    onChange={(event) => form.setData('publication_year', event.target.value)}
                                />
                            </Field>
                            <Field label="Journal" error={errors.journal}>
                                <input
                                    className="field"
                                    value={form.data.journal}
                                    onChange={(event) => form.setData('journal', event.target.value)}
                                />
                            </Field>
                            <Field label="DOI" error={errors.doi}>
                                <input
                                    className="field"
                                    value={form.data.doi}
                                    onChange={(event) => form.setData('doi', event.target.value)}
                                    placeholder="10.xxxx/…"
                                />
                            </Field>
                            <Field label="Keywords" error={errors.keywords}>
                                <input
                                    className="field"
                                    value={form.data.keywords}
                                    onChange={(event) => form.setData('keywords', event.target.value)}
                                    placeholder="Comma separated"
                                />
                            </Field>
                        </div>
                    </Card>

                    <Card className="p-6">
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h2 className="font-semibold">Authors</h2>
                                <p className="mt-1 text-sm text-slate-500">
                                    Author order is preserved from top to bottom.
                                </p>
                            </div>
                            <button
                                type="button"
                                className="btn-secondary"
                                disabled={form.data.authors.length >= 100}
                                onClick={() => form.setData('authors', [...form.data.authors, blankAuthor()])}
                            >
                                <UserPlus className="h-4 w-4" /> Add author
                            </button>
                        </div>
                        <InputError message={errors.authors} className="mt-3" />
                        <div className="mt-5 space-y-4">
                            {form.data.authors.map((author, index) => (
                                <div key={index} className="rounded-xl border border-slate-200 p-4">
                                    <div className="mb-4 flex items-center justify-between">
                                        <h3 className="text-sm font-semibold">Author {index + 1}</h3>
                                        <button
                                            type="button"
                                            className="rounded-lg p-2 text-slate-500 hover:bg-rose-50 hover:text-rose-700"
                                            aria-label={`Remove author ${index + 1}`}
                                            onClick={() => removeAuthor(index)}
                                        >
                                            <Trash2 className="h-4 w-4" />
                                        </button>
                                    </div>
                                    <div className="grid gap-4 sm:grid-cols-2">
                                        {(['name', 'email', 'affiliation', 'orcid'] as const).map((field) => (
                                            <Field
                                                key={field}
                                                label={field === 'orcid' ? 'ORCID' : field[0].toUpperCase() + field.slice(1)}
                                                error={errors[`authors.${index}.${field}`]}
                                            >
                                                <input
                                                    required={field === 'name'}
                                                    type={field === 'email' ? 'email' : 'text'}
                                                    className="field"
                                                    value={author[field]}
                                                    onChange={(event) => setAuthor(index, field, event.target.value)}
                                                />
                                            </Field>
                                        ))}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </Card>

                    <div className="flex flex-wrap justify-end gap-3">
                        <Link href={`/papers/${paper.id}`} className="btn-secondary">
                            Cancel
                        </Link>
                        <button disabled={form.processing} className="btn-primary">
                            {form.processing ? 'Saving…' : 'Save changes'}
                        </button>
                    </div>
                </form>
            </div>
        </AuthenticatedLayout>
    );
}

function Field({
    label,
    error,
    wide,
    children,
}: {
    label: string;
    error?: string;
    wide?: boolean;
    children: React.ReactNode;
}) {
    return (
        <label className={wide ? 'sm:col-span-2' : ''}>
            <span className="mb-2 block text-sm font-semibold">{label}</span>
            {children}
            <InputError message={error} className="mt-2" />
        </label>
    );
}
