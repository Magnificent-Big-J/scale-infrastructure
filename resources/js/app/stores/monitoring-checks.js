import { defineStore } from 'pinia';

import { v1 } from '../utils/api';

export const useMonitoringChecksStore = defineStore('monitoring-checks', {
    state: () => ({
        rows: [],
        meta: { current_page: 1, last_page: 1, per_page: 10, total: 0 },
        options: { statuses: [], check_types: [], deployments: [] },
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

                const response = await v1(`monitoring-checks?${params}`);

                this.rows = response?.data?.map((item) => item?.data ?? item) ?? [];
                this.meta = response?.meta ?? this.meta;
                this.options = response?.options ?? this.options;

                return response;
            } finally {
                this.loading = false;
            }
        },

        async create(deploymentId, payload) {
            this.loading = true;

            try {
                const response = await v1(`deployments/${deploymentId}/monitoring-checks`, { method: 'POST', body: payload });

                return response?.data ?? response;
            } finally {
                this.loading = false;
            }
        },

        async update(checkId, payload) {
            this.loading = true;

            try {
                const response = await v1(`monitoring-checks/${checkId}`, { method: 'PATCH', body: payload });

                return response?.data ?? response;
            } finally {
                this.loading = false;
            }
        },
    },
});
