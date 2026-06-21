import { defineStore } from 'pinia';

import { v1 } from '../utils/api';

export const useReleasesStore = defineStore('releases', {
    state: () => ({
        rows: [],
        meta: { current_page: 1, last_page: 1, per_page: 10, total: 0 },
        options: { statuses: [], deployments: [], change_requests: [] },
        loading: false,
    }),
    actions: {
        upsertRow(record) {
            if (!record?.id) return;

            const index = this.rows.findIndex((row) => row.id === record.id);

            if (index === -1) {
                this.rows.unshift(record);
                this.meta.total += 1;
            } else {
                this.rows.splice(index, 1, { ...this.rows[index], ...record });
            }
        },

        async fetch({ page = 1, perPage = 10, search = '', status = '' } = {}) {
            this.loading = true;

            try {
                const params = new URLSearchParams({ page, per_page: perPage });
                if (search) params.set('search', search);
                if (status) params.set('status', status);

                const response = await v1(`releases?${params}`);

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
                return await v1('releases', { method: 'POST', body: payload });
            } finally {
                this.loading = false;
            }
        },

        async update(releaseId, payload) {
            this.loading = true;

            try {
                return await v1(`releases/${releaseId}`, { method: 'PATCH', body: payload });
            } finally {
                this.loading = false;
            }
        },

        async transition(releaseId, action, payload = {}) {
            this.loading = true;

            try {
                return await v1(`releases/${releaseId}/${action}`, { method: 'POST', body: payload });
            } finally {
                this.loading = false;
            }
        },
    },
});
