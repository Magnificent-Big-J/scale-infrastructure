import { defineStore } from 'pinia';

import { v1 } from '../utils/api';

export const useSupportTicketsStore = defineStore('support-tickets', {
    state: () => ({
        rows: [],
        meta: { current_page: 1, last_page: 1, per_page: 10, total: 0 },
        options: { statuses: [], severities: [], categories: [], clients: [], deployments: [], agreements: [], users: [] },
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

        removeRow(id) {
            const before = this.rows.length;
            this.rows = this.rows.filter((row) => row.id !== id);

            if (this.rows.length < before) {
                this.meta.total = Math.max(0, this.meta.total - 1);
            }
        },

        async fetch({ page = 1, perPage = 10, search = '', status = '', severity = '' } = {}) {
            this.loading = true;

            try {
                const params = new URLSearchParams({ page, per_page: perPage });
                if (search) params.set('search', search);
                if (status) params.set('status', status);
                if (severity) params.set('severity', severity);

                const response = await v1(`support-tickets?${params}`);

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
                const response = await v1('support-tickets', { method: 'POST', body: payload });

                return response?.data ?? response;
            } finally {
                this.loading = false;
            }
        },

        async update(ticketId, payload) {
            this.loading = true;

            try {
                const response = await v1(`support-tickets/${ticketId}`, { method: 'PATCH', body: payload });

                return response?.data ?? response;
            } finally {
                this.loading = false;
            }
        },
    },
});
