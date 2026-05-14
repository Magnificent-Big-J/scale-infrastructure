import { ofetch } from 'ofetch';

const defaults = {
    credentials: 'include',
    retry: 0,
    headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    },
};

export const api = ofetch.create(defaults);

export const v1 = ofetch.create({ ...defaults, baseURL: '/api/v1' });
