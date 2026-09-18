import { describe, expect, it } from 'vitest';
import { asArray, displayDate, normalizeRole, paperStatus } from './contracts';

describe('frontend data contract adapters', () => {
    it('normalizes role shapes from Laravel resources', () => {
        expect(normalizeRole({ role: 'ADMIN' })).toBe('admin');
        expect(normalizeRole({ roles: [{ name: 'Reviewer' }] })).toBe('reviewer');
        expect(normalizeRole({ roles: ['researcher'] })).toBe('researcher');
        expect(normalizeRole({})).toBeNull();
        expect(normalizeRole({ role: 'unknown' })).toBeNull();
    });

    it('accepts arrays and Laravel paginators without inventing records', () => {
        expect(asArray([1, 2])).toEqual([1, 2]);
        expect(asArray({ data: [3], current_page: 1 })).toEqual([3]);
        expect(asArray(undefined)).toEqual([]);
    });

    it('maps backend paper states to accessible UI metadata', () => {
        expect(paperStatus('PROCESSING').label).toBe('Processing');
        expect(paperStatus('unexpected').label).toBe('Unknown');
    });

    it('returns an em dash for absent dates', () => {
        expect(displayDate(null)).toBe('—');
    });
});
