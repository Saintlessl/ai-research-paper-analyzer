import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it, vi } from 'vitest';

const formMocks = vi.hoisted(() => ({
    post: vi.fn(),
    setData: vi.fn(),
    useForm: vi.fn((initial: Record<string, unknown>) => ({
        data: initial,
        setData: vi.fn(),
        post: vi.fn(),
        processing: false,
        progress: null,
        errors: {},
    })),
}));

vi.mock('@inertiajs/react', () => ({
    Head: () => null,
    Link: ({ children, href, ...props }: React.PropsWithChildren<{ href: string }>) => (
        <a href={href} {...props}>
            {children}
        </a>
    ),
    useForm: formMocks.useForm,
}));

vi.mock('@/Layouts/AuthenticatedLayout', () => ({
    default: ({ children }: React.PropsWithChildren) => <>{children}</>,
}));

import Create from './Create';

describe('paper upload form', () => {
    it('uses server limits and initializes normalized author input', () => {
        const html = renderToStaticMarkup(
            <Create maxUploadMegabytes={17} maxPublicationYear={2042} />,
        );

        expect(html).toContain('max="2042"');
        expect(html).toContain('Maximum 17 MB');
        expect(html).toContain('Author 1');
        expect(html).toContain('Add author');
        expect(formMocks.useForm).toHaveBeenCalledWith(
            expect.objectContaining({
                authors: [
                    {
                        name: '',
                        email: '',
                        affiliation: '',
                        orcid: '',
                    },
                ],
            }),
        );
    });
});
