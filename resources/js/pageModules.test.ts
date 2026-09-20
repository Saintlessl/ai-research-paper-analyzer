import { describe, expect, it } from 'vitest';
import { pageModules } from './pageModules';

describe('Inertia page module discovery', () => {
    it('never includes test files in the production page graph', () => {
        expect(Object.keys(pageModules)).not.toEqual(
            expect.arrayContaining([expect.stringMatching(/\.test\.tsx$/)]),
        );
        expect(Object.keys(pageModules)).toContain('./Pages/Papers/Create.tsx');
    });
});
