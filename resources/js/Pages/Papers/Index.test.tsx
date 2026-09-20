import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it, vi } from 'vitest';

vi.mock('@inertiajs/react', () => ({
    Head: () => null,
    Link: ({ children, href }: React.PropsWithChildren<{ href: string }>) => (
        <a href={href}>
            {children}
        </a>
    ),
    router: { get: vi.fn() },
}));

vi.mock('@/Layouts/AuthenticatedLayout', () => ({
    default: ({ children }: React.PropsWithChildren) => <>{children}</>,
}));

import Index from './Index';

describe('paper library', () => {
    it('renders upload actions only when Laravel grants create capability', () => {
        const denied = renderToStaticMarkup(
            <Index papers={[]} filters={{}} can={{ create: false }} />,
        );
        const allowed = renderToStaticMarkup(
            <Index papers={[]} filters={{}} can={{ create: true }} />,
        );

        expect(denied).not.toContain('/papers/create');
        expect(denied).not.toContain('Upload paper');
        expect(allowed).toContain('href="/papers/create"');
        expect(allowed).toContain('Upload paper');
    });

    it('uses server status options and renders paginator navigation', () => {
        const html = renderToStaticMarkup(
            <Index
                papers={{
                    data: [{ id: 7, title: 'Paginated paper', status: 'UPLOADED' }],
                    current_page: 1,
                    last_page: 2,
                    total: 21,
                    links: [
                        { url: null, label: '&laquo; Previous', active: false },
                        { url: '/papers?page=1', label: '1', active: true },
                        { url: '/papers?page=2', label: '2', active: false },
                        { url: '/papers?page=2', label: 'Next &raquo;', active: false },
                    ],
                }}
                filters={{}}
                can={{ create: false }}
                statusOptions={[
                    { value: 'uploaded', label: 'Uploaded' },
                    { value: 'failed', label: 'Failed' },
                ]}
            />,
        );

        expect(html).toContain('<option value="uploaded">Uploaded</option>');
        expect(html).toContain('<option value="failed">Failed</option>');
        expect(html).not.toContain('value="queued"');
        expect(html).toContain('href="/papers?page=2"');
        expect(html).toContain('Page 1 of 2');
        expect(html).toContain('>Previous<');
        expect(html).toContain('>Next<');
        expect(html).not.toContain('&amp;laquo;');
        expect(html).not.toContain('&amp;raquo;');
    });
});
