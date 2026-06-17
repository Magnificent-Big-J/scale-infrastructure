import { defineStore } from 'pinia';

import { v1 } from '../utils/api';

export const useSupportTiersStore = defineStore('support-tiers', {
    state: () => ({
        rows: [],
        meta: {
            current_page: 1,
            last_page: 1,
            per_page: 10,
            total: 0,
        },
        options: {
            statuses: [],
            default_currency: '',
        },
        loading: false,
    }),
    actions: {
        async fetch({ page = 1, perPage = 10, search = '', status = '' } = {}) {
            this.loading = true;

            try {
                const params = new URLSearchParams({ page, per_page: perPage });

                if (search) params.set('search', search);
                if (status) params.set('status', status);

                const response = await v1(`support-tiers?${params}`);

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
                const response = await v1('support-tiers', { method: 'POST', body: payload });

                return response?.data ?? response;
            } finally {
                this.loading = false;
            }
        },

        async update(supportTierId, payload) {
            this.loading = true;

            try {
                const response = await v1(`support-tiers/${supportTierId}`, { method: 'PATCH', body: payload });

                return response?.data ?? response;
            } finally {
                this.loading = false;
            }
        },

        async archive(supportTierId) {
            this.loading = true;

            try {
                return await v1(`support-tiers/${supportTierId}`, { method: 'DELETE' });
            } finally {
                this.loading = false;
            }
        },
    },
});
