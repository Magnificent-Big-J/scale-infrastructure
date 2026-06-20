import { defineStore } from 'pinia';

import { v1 } from '../utils/api';

export const useProfitabilityStore = defineStore('profitability', {
    state: () => ({
        rows: [],
        meta: { current_page: 1, last_page: 1, per_page: 10, total: 0 },
        summary: { revenue_total: 0, cost_total: 0, profit_total: 0, margin_avg: 0, records: 0 },
        options: { clients: [] },
        loading: false,
    }),
    actions: {
        async fetch({ page = 1, perPage = 10, search = '', period = '' } = {}) {
            this.loading = true;

            try {
                const params = new URLSearchParams({ page, per_page: perPage });
                if (search) params.set('search', search);
                if (period) params.set('period', period);

                const response = await v1(`profitability-records?${params}`);

                this.rows = response?.data?.map((item) => item?.data ?? item) ?? [];
                this.meta = response?.meta ?? this.meta;
                this.summary = response?.summary ?? this.summary;
                this.options = response?.options ?? this.options;

                return response;
            } finally {
                this.loading = false;
            }
        },

        async create(payload) {
            this.loading = true;

            try {
                const response = await v1('profitability-records', { method: 'POST', body: payload });

                return response?.data ?? response;
            } finally {
                this.loading = false;
            }
        },

        async update(recordId, payload) {
            this.loading = true;

            try {
                const response = await v1(`profitability-records/${recordId}`, { method: 'PATCH', body: payload });

                return response?.data ?? response;
            } finally {
                this.loading = false;
            }
        },
    },
});
