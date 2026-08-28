import { beforeEach, describe, expect, it } from 'vitest';
import {
    extractUserPayload,
    getXsrfToken,
    loadPendingTwoFactorState,
    normalizeErrorMessage,
    persistPendingTwoFactorState,
    validationErrors,
} from './auth-shared';

describe('extractUserPayload', () => {
    it('prefers response.user', () => {
        expect(extractUserPayload({ user: { id: 1 }, data: { id: 2 } })).toEqual({ id: 1 });
    });

    it('falls back to response.data.user', () => {
        expect(extractUserPayload({ data: { user: { id: 2 } } })).toEqual({ id: 2 });
    });

    it('falls back to response.data', () => {
        expect(extractUserPayload({ data: { id: 3 } })).toEqual({ id: 3 });
    });

    it('returns null for an empty response', () => {
        expect(extractUserPayload(null)).toBeNull();
        expect(extractUserPayload(undefined)).toBeNull();
    });
});

describe('normalizeErrorMessage', () => {
    it('prefers error.data.message', () => {
        expect(normalizeErrorMessage({ data: { message: 'Server said no.' } })).toBe('Server said no.');
    });

    it('falls back to error.data.error', () => {
        expect(normalizeErrorMessage({ data: { error: 'Bad request.' } })).toBe('Bad request.');
    });

    it('falls back to error.message', () => {
        expect(normalizeErrorMessage({ message: 'Network failure.' })).toBe('Network failure.');
    });

    it('falls back to the provided default', () => {
        expect(normalizeErrorMessage({}, 'Custom fallback.')).toBe('Custom fallback.');
    });
});

describe('validationErrors', () => {
    it('extracts the errors bag', () => {
        expect(validationErrors({ data: { errors: { email: ['Required.'] } } })).toEqual({ email: ['Required.'] });
    });

    it('returns an empty object when there is no errors bag', () => {
        expect(validationErrors({})).toEqual({});
    });
});

describe('pending two-factor state (localStorage round-trip)', () => {
    beforeEach(() => {
        localStorage.clear();
    });

    it('defaults to not required when nothing is stored', () => {
        expect(loadPendingTwoFactorState()).toEqual({ required: false, channel: null });
    });

    it('persists and reloads a required state with its channel', () => {
        persistPendingTwoFactorState(true, 'email');

        expect(loadPendingTwoFactorState()).toEqual({ required: true, channel: 'email' });
    });

    it('clears storage when persisted as not required', () => {
        persistPendingTwoFactorState(true, 'email');
        persistPendingTwoFactorState(false);

        expect(loadPendingTwoFactorState()).toEqual({ required: false, channel: null });
    });

    it('recovers from corrupted JSON instead of throwing', () => {
        localStorage.setItem('rw-starter.pending-2fa', '{not json');

        expect(loadPendingTwoFactorState()).toEqual({ required: false, channel: null });
    });
});

describe('getXsrfToken', () => {
    it('returns an empty string when the cookie is absent', () => {
        expect(getXsrfToken()).toBe('');
    });

    it('reads and decodes the XSRF-TOKEN cookie', () => {
        document.cookie = 'XSRF-TOKEN=abc%3D123';

        expect(getXsrfToken()).toBe('abc=123');
    });
});
