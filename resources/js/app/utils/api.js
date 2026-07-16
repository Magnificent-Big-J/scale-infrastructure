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

export const v1 = ofetch.create({
    ...defaults,
    baseURL: '/api/v1',
    onResponseError({ response }) {
        const path = window.location?.pathname || '';

        if (response.status === 403 && response._data?.code === 'two_factor_setup_required' && path !== '/profile') {
            window.location.assign('/profile?require2fa=1');
        }
    },
});
