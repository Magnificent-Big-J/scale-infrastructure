import { describe, expect, it } from 'vitest';
import { errorMessage } from './useToast';

describe('errorMessage', () => {
    it('extracts the server-provided message', () => {
        expect(errorMessage({ data: { message: 'Could not save.' } })).toBe('Could not save.');
    });

    it('falls back to the provided default when there is no message', () => {
        expect(errorMessage({}, 'Something went wrong.')).toBe('Something went wrong.');
    });

    it('falls back to a generic default when none is provided', () => {
        expect(errorMessage(null)).toBe('Something went wrong.');
    });
});
