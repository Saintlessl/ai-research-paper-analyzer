import React, { FormEvent, useState, useEffect } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, Link, router } from '@inertiajs/react';
import { ShieldCheck, UserPlus, Trash2, LoaderCircle } from 'lucide-react';
import { NeuCard } from '@/Components/ui/NeuCard';
import { NeuButton } from '@/Components/ui/NeuButton';
import { NeuInput } from '@/Components/ui/NeuInput';
import { NeuTextarea, NeuFileDropzone } from '@/Components/ui/NeuForm';
import { NeuStatusPill } from '@/Components/ui/NeuData';

type AuthorInput = {
    name: string;
    email: string;
    affiliation: string;
    orcid: string;
};

const blankAuthor = (): AuthorInput => ({ name: '', email: '', affiliation: '', orcid: '' });

export default function Create({
    maxUploadMegabytes,
    maxPublicationYear,
}: {
    maxUploadMegabytes: number;
    maxPublicationYear: number;
}) {
    const [title, setTitle] = useState('');
    const [abstract, setAbstract] = useState('');
    const [publicationYear, setPublicationYear] = useState('');
    const [journal, setJournal] = useState('');
    const [doi, setDoi] = useState('');
    const [keywords, setKeywords] = useState('');
    const [authors, setAuthors] = useState<AuthorInput[]>([blankAuthor()]);
    const [file, setFile] = useState<File | null>(null);
    const [fileError, setFileError] = useState<string | undefined>();
    
    // Upload state
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [uploadProgress, setUploadProgress] = useState<number>(0);
    const [uploadError, setUploadError] = useState<string | undefined>();
    
    // Polling state (simulated UI for now, but ready for actual paper ID)
    const [paperId, setPaperId] = useState<number | null>(null);
    const [processingStatus, setProcessingStatus] = useState<'UPLOADED' | 'EXTRACTING' | 'CHUNKING' | 'ANALYZING' | 'VALIDATING' | 'COMPLETED' | 'FAILED' | null>(null);

    const validateFile = (selectedFile: File): boolean => {
        setFileError(undefined);
        if (selectedFile.type !== 'application/pdf' && !selectedFile.name.toLowerCase().endsWith('.pdf')) {
            setFileError('Hanya file PDF yang diizinkan.');
            return false;
        }
        if (selectedFile.size > maxUploadMegabytes * 1024 * 1024) {
            setFileError(`Ukuran file melebihi batas maksimal ${maxUploadMegabytes}MB.`);
            return false;
        }
        setFile(selectedFile);
        return true;
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();
        if (!file) {
            setFileError('Pilih file PDF terlebih dahulu.');
            return;
        }
        
        setIsSubmitting(true);
        setProcessingStatus('UPLOADED');
        setUploadError(undefined);

        const formData = new FormData();
        formData.append('title', title);
        formData.append('abstract', abstract);
        formData.append('publication_year', publicationYear);
        formData.append('journal', journal);
        formData.append('doi', doi);
        formData.append('keywords', keywords);
        formData.append('file', file);
        authors.forEach((author, i) => {
            formData.append(`authors[${i}][name]`, author.name);
            formData.append(`authors[${i}][email]`, author.email);
            formData.append(`authors[${i}][affiliation]`, author.affiliation);
            formData.append(`authors[${i}][orcid]`, author.orcid);
        });

        router.post('/papers', formData, {
            forceFormData: true,
            preserveScroll: true,
            onProgress: (progress: any) => {
                setUploadProgress(progress?.percentage || 0);
            },
            onSuccess: (page) => {
                // Instantly switch to processing status on success
                setProcessingStatus('EXTRACTING');
                
                // Usually the backend will redirect to the show page, 
                // but if we stay on this page, we'd start polling.
                // Assuming backend redirects, this state will be transient.
            },
            onError: (errors) => {
                setIsSubmitting(false);
                setProcessingStatus(null);
                setUploadError(errors.file || errors.title || 'Upload gagal. Silakan periksa kembali form Anda.');
            }
        });
    };

    const setAuthor = (index: number, field: keyof AuthorInput, value: string) => {
        setAuthors(authors.map((author, i) => i === index ? { ...author, [field]: value } : author));
    };

    if (processingStatus) {
        return (
            <AuthenticatedLayout>
                <Head title="Uploading..." />
                <div className="flex items-center justify-center min-h-[70vh]">
                    <NeuCard padding="lg" className="w-full max-w-md text-center">
                        <div className="relative mx-auto w-16 h-16 mb-6 flex items-center justify-center rounded-full bg-neu-surface neu-pressed text-neu-accent-text">
                            <LoaderCircle className="w-8 h-8 animate-spin" />
                        </div>
                        <h2 className="text-xl font-bold mb-2">Processing Paper</h2>
                        <p className="text-neu-muted text-sm mb-6">
                            {processingStatus === 'UPLOADED' && `Uploading to server... ${uploadProgress}%`}
                            {processingStatus === 'EXTRACTING' && 'Extracting PDF contents...'}
                            {processingStatus === 'CHUNKING' && 'Chunking document for AI...'}
                            {processingStatus === 'ANALYZING' && 'AI is analyzing research...'}
                            {processingStatus === 'VALIDATING' && 'Validating results...'}
                        </p>
                        
                        <div className="w-full bg-neu-surface neu-pressed h-3 rounded-neu-pill overflow-hidden">
                            <div 
                                className="h-full bg-neu-accent-fill transition-all duration-300"
                                style={{ width: `${
                                    processingStatus === 'UPLOADED' ? uploadProgress : 
                                    processingStatus === 'EXTRACTING' ? 30 :
                                    processingStatus === 'CHUNKING' ? 50 :
                                    processingStatus === 'ANALYZING' ? 70 :
                                    processingStatus === 'VALIDATING' ? 90 : 100
                                }%` }}
                            />
                        </div>
                    </NeuCard>
                </div>
            </AuthenticatedLayout>
        );
    }

    return (
        <AuthenticatedLayout 
            header={
                <div>
                    <h2 className="text-2xl font-bold leading-tight">Upload Research Paper</h2>
                    <p className="text-sm text-neu-muted mt-1">Store a private PDF with normalized academic metadata.</p>
                </div>
            }
        >
            <Head title="Upload paper" />
            
            <form onSubmit={submit} className="grid gap-6 lg:grid-cols-[1.4fr_.6fr] max-w-6xl mx-auto">
                <div className="space-y-6">
                    <NeuCard padding="lg" className="space-y-5">
                        <h3 className="font-bold border-b border-neu-hairline pb-2 mb-4">Metadata</h3>
                        <div className="grid gap-5 sm:grid-cols-2">
                            <div className="sm:col-span-2">
                                <label className="block text-sm font-semibold mb-2 text-neu-text">Title</label>
                                <NeuInput required value={title} onChange={e => setTitle(e.target.value)} />
                            </div>
                            <div className="sm:col-span-2">
                                <label className="block text-sm font-semibold mb-2 text-neu-text">Abstract</label>
                                <NeuTextarea className="min-h-28" value={abstract} onChange={e => setAbstract(e.target.value)} />
                            </div>
                            <div>
                                <label className="block text-sm font-semibold mb-2 text-neu-text">Publication Year</label>
                                <NeuInput type="number" min="1600" max={maxPublicationYear} value={publicationYear} onChange={e => setPublicationYear(e.target.value)} />
                            </div>
                            <div>
                                <label className="block text-sm font-semibold mb-2 text-neu-text">Journal</label>
                                <NeuInput value={journal} onChange={e => setJournal(e.target.value)} />
                            </div>
                            <div>
                                <label className="block text-sm font-semibold mb-2 text-neu-text">DOI</label>
                                <NeuInput value={doi} onChange={e => setDoi(e.target.value)} placeholder="10.xxxx/…" />
                            </div>
                            <div>
                                <label className="block text-sm font-semibold mb-2 text-neu-text">Keywords</label>
                                <NeuInput value={keywords} onChange={e => setKeywords(e.target.value)} placeholder="Comma separated" />
                            </div>
                        </div>
                    </NeuCard>

                    <NeuCard padding="lg">
                        <div className="flex flex-wrap items-center justify-between gap-3 mb-6 pb-2 border-b border-neu-hairline">
                            <div>
                                <h3 className="font-bold text-neu-text">Authors</h3>
                                <p className="text-xs text-neu-muted mt-1">Order is preserved from top to bottom.</p>
                            </div>
                            <NeuButton type="button" variant="secondary" disabled={authors.length >= 100} onClick={() => setAuthors([...authors, blankAuthor()])}>
                                <UserPlus className="h-4 w-4 mr-2" /> Add Author
                            </NeuButton>
                        </div>
                        
                        <div className="space-y-4">
                            {authors.map((author, index) => (
                                <div key={index} className="rounded-neu-md p-5 bg-neu-surface border border-neu-hairline neu-flat">
                                    <div className="mb-4 flex items-center justify-between">
                                        <h4 className="text-sm font-bold text-neu-text">Author {index + 1}</h4>
                                        <button type="button" className="rounded-full p-2 text-neu-muted hover:bg-status-failed-fill/10 hover:text-status-failed-text transition-colors" aria-label={`Remove author ${index + 1}`} onClick={() => setAuthors(authors.filter((_, i) => i !== index))}>
                                            <Trash2 className="h-4 w-4" />
                                        </button>
                                    </div>
                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <div>
                                            <label className="block text-xs font-semibold mb-1 text-neu-muted uppercase">Name</label>
                                            <NeuInput required value={author.name} onChange={e => setAuthor(index, 'name', e.target.value)} />
                                        </div>
                                        <div>
                                            <label className="block text-xs font-semibold mb-1 text-neu-muted uppercase">Email</label>
                                            <NeuInput type="email" value={author.email} onChange={e => setAuthor(index, 'email', e.target.value)} />
                                        </div>
                                        <div>
                                            <label className="block text-xs font-semibold mb-1 text-neu-muted uppercase">Affiliation</label>
                                            <NeuInput value={author.affiliation} onChange={e => setAuthor(index, 'affiliation', e.target.value)} />
                                        </div>
                                        <div>
                                            <label className="block text-xs font-semibold mb-1 text-neu-muted uppercase">ORCID</label>
                                            <NeuInput value={author.orcid} onChange={e => setAuthor(index, 'orcid', e.target.value)} />
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </NeuCard>
                </div>

                <div className="space-y-5">
                    <NeuCard padding="lg" className="sticky top-24">
                        <label className="block text-sm font-semibold mb-3 text-neu-text">PDF Document</label>
                        
                        {/* Custom NeuFileDropzone handling file display internally or we wrap it */}
                        <div className="relative">
                            <NeuFileDropzone 
                                accept="application/pdf,.pdf"
                                onDrop={(f) => validateFile(f)}
                                error={fileError}
                            />
                            {/* Display file name clearly */}
                            <div className="pointer-events-none mt-2 text-center">
                                {file ? (
                                    <span className="text-sm font-bold text-status-analyzed-text break-words">📄 {file.name} ({(file.size / (1024 * 1024)).toFixed(2)} MB)</span>
                                ) : (
                                    <span className="text-xs font-semibold text-neu-muted uppercase tracking-wide">PDF only • Maximum {maxUploadMegabytes} MB</span>
                                )}
                            </div>
                        </div>

                        {uploadError && <p className="mt-4 text-sm font-semibold text-status-failed-text bg-status-failed-fill/10 p-3 rounded-neu-sm">{uploadError}</p>}

                        <div className="mt-6 flex gap-3">
                            <Link href="/papers" className="flex-1">
                                <NeuButton variant="secondary" className="w-full">Cancel</NeuButton>
                            </Link>
                            <NeuButton type="submit" variant="primary" className="flex-1" disabled={isSubmitting}>
                                {isSubmitting ? 'Uploading…' : 'Upload Paper'}
                            </NeuButton>
                        </div>

                        <div className="mt-6 p-4 rounded-neu-md bg-neu-surface neu-flat flex gap-3 text-xs leading-5 text-neu-muted">
                            <ShieldCheck className="h-5 w-5 shrink-0 text-status-analyzed-text" />
                            The server validates file contents and stores the PDF securely outside the public web root.
                        </div>
                    </NeuCard>
                </div>
            </form>
        </AuthenticatedLayout>
    );
}