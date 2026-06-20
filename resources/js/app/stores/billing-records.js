import { defineStore } from 'pinia';

import { v1 } from '../utils/api';

export const useBillingRecordsStore = defineStore('billing-records', {
    state: () => ({
        rows: [],
        meta: { current_page: 1, last_page: 1, per_page: 10, total: 0 },
        options: { types: [], cadences: [], clients: [], contracts: [] },
        loading: false,
    }),
    actions: {
        async fetch({ page = 1, perPage = 10, search = '', type = '', cadence = '' } = {}) {
            this.loading = true;

            try {
                const params = new URLSearchParams({ page, per_page: perPage });
                if (search) params.set('search', search);
                if (type) params.set('type', type);
                if (cadence) params.set('cadence', cadence);

                const response = await v1(`billing-records?${params}`);

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
                const response = await v1('billing-records', { method: 'POST', body: payload });

                return response?.data ?? response;
            } finally {
                this.loading = false;
            }
        },

        async update(recordId, payload) {
            this.loading = true;

            try {
                const response = await v1(`billing-records/${recordId}`, { method: 'PATCH', body: payload });

                return response?.data ?? response;
            } finally {
                this.loading = false;
            }
        },
    },
});
