import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it, vi } from 'vitest';

const formMocks = vi.hoisted(() => ({
    patch: vi.fn(),
    setData: vi.fn(),
    useForm: vi.fn((initial: Record<string, unknown>) => ({
        data: initial,
        setData: vi.fn(),
        patch: vi.fn(),
        processing: false,
        errors: {},
    })),
}));

vi.mock('@inertiajs/react', () => ({
    Head: () => null,
    Link: ({ children, href }: React.PropsWithChildren<{ href: string }>) => (
        <a href={href}>{children}</a>
    ),
    useForm: formMocks.useForm,
}));

vi.mock('@/Layouts/AuthenticatedLayout', () => ({
    default: ({ children }: React.PropsWithChildren) => <>{children}</>,
}));

import Edit from './Edit';

describe('paper edit form', () => {
    it('enforces the server author limit in the UI', () => {
        const authors = Array.from({ length: 100 }, (_, index) => ({
            name: `Author ${index + 1}`,
        }));
        const html = renderToStaticMarkup(
            <Edit
                paper={{ id: 7, title: 'Paper', authors }}
                maxPublicationYear={2042}
            />,
        );

        expect(html).toMatch(/<button type="button" class="btn-secondary" disabled="">[\s\S]*?Add author<\/button>/);
        expect(formMocks.useForm).toHaveBeenCalledWith(
            expect.objectContaining({ authors: expect.any(Array) }),
        );
    });
});
