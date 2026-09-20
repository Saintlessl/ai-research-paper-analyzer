import { renderToStaticMarkup } from 'react-dom/server';
import { beforeAll, describe, expect, it, vi } from 'vitest';

const page = vi.hoisted(() => ({
    props: {
        auth: {
            user: {
                id: 1,
                name: 'Research Reviewer',
                email: 'research-reviewer@example.test',
                role: 'reviewer' as const,
                roles: ['reviewer', 'researcher'] as Array<'reviewer' | 'researcher'>,
            },
            capabilities: {
                upload_papers: true,
                review_papers: true,
                manage_system: false,
            },
        },
        flash: {},
    },
}));

vi.mock('@inertiajs/react', () => ({
    Link: ({ children, href }: React.PropsWithChildren<{ href: string }>) => (
        <a href={href}>{children}</a>
    ),
    usePage: () => page,
}));

vi.mock('@/Components/ApplicationLogo', () => ({
    default: () => <span>Logo</span>,
}));

import AuthenticatedLayout from './AuthenticatedLayout';

beforeAll(() => {
    vi.stubGlobal('window', { location: { pathname: '/dashboard' } });
});

describe('authenticated navigation', () => {
    it('uses combined capabilities and only links implemented paper routes', () => {
        const html = renderToStaticMarkup(
            <AuthenticatedLayout>
                <div>Content</div>
            </AuthenticatedLayout>,
        );

        expect(html).toContain('href="/papers"');
        expect(html).toContain('href="/papers/create"');
        expect(html).not.toContain('/reviewer/papers');
        expect(html).not.toContain('/admin/papers');
        expect(html).not.toContain('/reviewer/reviews');
        expect(html).not.toContain('/admin/');
    });

    it('does not show upload navigation without upload capability', () => {
        page.props.auth.user.role = 'reviewer';
        page.props.auth.user.roles = ['reviewer'];
        page.props.auth.capabilities.upload_papers = false;

        const html = renderToStaticMarkup(
            <AuthenticatedLayout>
                <div>Content</div>
            </AuthenticatedLayout>,
        );

        expect(html).toContain('href="/papers"');
        expect(html).not.toContain('/papers/create');
    });
});
