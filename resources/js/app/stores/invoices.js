import { defineStore } from 'pinia';

import { v1 } from '../utils/api';

export const useInvoicesStore = defineStore('invoices', {
    state: () => ({
        rows: [],
        meta: { current_page: 1, last_page: 1, per_page: 10, total: 0 },
        options: { statuses: [], clients: [], contracts: [] },
        loading: false,
    }),
    actions: {
        async fetch({ page = 1, perPage = 10, search = '', status = '' } = {}) {
            this.loading = true;

            try {
                const params = new URLSearchParams({ page, per_page: perPage });
                if (search) params.set('search', search);
                if (status) params.set('status', status);

                const response = await v1(`invoices?${params}`);

                this.rows = response?.data?.map((item) => item?.data ?? item) ?? [];
                this.meta = response?.meta ?? this.meta;
                this.options = response?.options ?? this.options;

                return response;
            } finally {
                this.loading = false;
            }
        },

        async show(invoiceId) {
            const response = await v1(`invoices/${invoiceId}`);

            return response?.data ?? response;
        },

        async create(payload) {
            this.loading = true;

            try {
                const response = await v1('invoices', { method: 'POST', body: payload });

                return response?.data ?? response;
            } finally {
                this.loading = false;
            }
        },

        async update(invoiceId, payload) {
            this.loading = true;

            try {
                const response = await v1(`invoices/${invoiceId}`, { method: 'PATCH', body: payload });

                return response?.data ?? response;
            } finally {
                this.loading = false;
            }
        },

        async recordPayment(invoiceId, payload) {
            this.loading = true;

            try {
                const response = await v1(`invoices/${invoiceId}/payments`, { method: 'POST', body: payload });

                return response?.data ?? response;
            } finally {
                this.loading = false;
            }
        },
    },
});
