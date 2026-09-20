import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it, vi } from 'vitest';

const page = vi.hoisted(() => ({
    props: {
        auth: {
            user: {
                id: 1,
                name: 'Research Reviewer',
                email: 'research-reviewer@example.test',
                role: 'reviewer' as const,
                roles: ['reviewer', 'researcher'] as const,
            },
            capabilities: {
                upload_papers: true,
                review_papers: true,
                manage_system: false,
            },
        },
        recentPapers: [],
    },
}));

vi.mock('@inertiajs/react', () => ({
    Head: () => null,
    Link: ({ children, href }: React.PropsWithChildren<{ href: string }>) => (
        <a href={href}>{children}</a>
    ),
    usePage: () => page,
}));

vi.mock('@/Layouts/AuthenticatedLayout', () => ({
    default: ({ children }: React.PropsWithChildren) => <>{children}</>,
}));

import Dashboard from './Dashboard';

describe('dashboard paper actions', () => {
    it('uses capabilities for upload and the implemented library route', () => {
        const html = renderToStaticMarkup(<Dashboard />);

        expect(html).toContain('href="/papers/create"');
        expect(html).toContain('href="/papers"');
        expect(html).not.toContain('/reviewer/papers');
        expect(html).not.toContain('/admin/papers');
    });

    it('hides upload without upload capability', () => {
        page.props.auth.capabilities.upload_papers = false;

        const html = renderToStaticMarkup(<Dashboard />);

        expect(html).not.toContain('/papers/create');
        expect(html).toContain('href="/papers"');
    });
});
