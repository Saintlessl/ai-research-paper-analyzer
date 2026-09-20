import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it, vi } from 'vitest';

vi.mock('@inertiajs/react', () => ({
    Head: () => null,
    Link: ({ children, href }: React.PropsWithChildren<{ href: string }>) => (
        <a href={href}>{children}</a>
    ),
    router: { delete: vi.fn() },
    useForm: vi.fn(() => ({
        data: { question: '' },
        setData: vi.fn(),
        post: vi.fn(),
        reset: vi.fn(),
        processing: false,
        errors: {},
    })),
}));

vi.mock('@/Layouts/AuthenticatedLayout', () => ({
    default: ({ children }: React.PropsWithChildren) => <>{children}</>,
}));

import Show from './Show';

const paper = {
    id: 42,
    title: 'Capability-scoped paper',
    status: 'UPLOADED',
    authors: [],
};

describe('paper workspace actions', () => {
    it('renders edit and delete actions only when Laravel grants each capability', () => {
        const denied = renderToStaticMarkup(
            <Show paper={paper} tab="overview" can={{ download: true, update: false, delete: false }} />,
        );
        const allowed = renderToStaticMarkup(
            <Show paper={paper} tab="overview" can={{ download: true, update: true, delete: true }} />,
        );

        expect(denied).not.toContain('/papers/42/edit');
        expect(denied).not.toContain('Delete paper');
        expect(allowed).toContain('href="/papers/42/edit"');
        expect(allowed).toContain('Delete paper');
    });
});
