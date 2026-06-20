import { defineStore } from 'pinia';

import { v1 } from '../utils/api';

export const useChangeRequestsStore = defineStore('change-requests', {
    state: () => ({
        rows: [],
        meta: { current_page: 1, last_page: 1, per_page: 10, total: 0 },
        options: { statuses: [], risks: [], clients: [], deployments: [] },
        loading: false,
    }),
    actions: {
        async fetch({ page = 1, perPage = 10, search = '', status = '' } = {}) {
            this.loading = true;

            try {
                const params = new URLSearchParams({ page, per_page: perPage });
                if (search) params.set('search', search);
                if (status) params.set('status', status);

                const response = await v1(`change-requests?${params}`);

                this.rows = response?.data?.map((item) => item?.data ?? item) ?? [];
                this.meta = response?.meta ?? this.meta;
                this.options = response?.options ?? this.options;

                return response;
            } finally {
                this.loading = false;
            }
        },

        async create(payload) {
            this.loading = true;

            try {
                return await v1('change-requests', { method: 'POST', body: payload });
            } finally {
                this.loading = false;
            }
        },

        async update(changeRequestId, payload) {
            this.loading = true;

            try {
                return await v1(`change-requests/${changeRequestId}`, { method: 'PATCH', body: payload });
            } finally {
                this.loading = false;
            }
        },

        async decide(changeRequestId, action) {
            this.loading = true;

            try {
                return await v1(`change-requests/${changeRequestId}/${action}`, { method: 'POST' });
            } finally {
                this.loading = false;
            }
        },
    },
});
