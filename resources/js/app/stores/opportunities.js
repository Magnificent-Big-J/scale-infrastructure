import { defineStore } from 'pinia';

import { v1 } from '../utils/api';

export const useOpportunitiesStore = defineStore('opportunities', {
    state: () => ({
        rows: [],
        meta: { current_page: 1, last_page: 1, per_page: 10, total: 0 },
        summary: { open_count: 0, open_value: 0, won_count: 0, won_value: 0, win_rate: 0 },
        pipeline: [],
        options: { stages: [], sources: [], clients: [], owners: [] },
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

        async fetch({ page = 1, perPage = 10, search = '', stage = '' } = {}) {
            this.loading = true;

            try {
                const params = new URLSearchParams({ page, per_page: perPage });
                if (search) params.set('search', search);
                if (stage) params.set('stage', stage);

                const response = await v1(`opportunities?${params}`);

                this.rows = response?.data?.map((item) => item?.data ?? item) ?? [];
                this.meta = response?.meta ?? this.meta;
                this.summary = response?.summary ?? this.summary;
                this.pipeline = response?.pipeline ?? this.pipeline;
                this.options = response?.options ?? this.options;

                return response;
            } finally {
                this.loading = false;
            }
        },

        async create(payload) {
            this.loading = true;

            try {
                const response = await v1('opportunities', { method: 'POST', body: payload });

                return response?.data ?? response;
            } finally {
                this.loading = false;
            }
        },

        async update(id, payload) {
            this.loading = true;

            try {
                const response = await v1(`opportunities/${id}`, { method: 'PATCH', body: payload });

                return response?.data ?? response;
            } finally {
                this.loading = false;
            }
        },

        async remove(id) {
            this.loading = true;

            try {
                return await v1(`opportunities/${id}`, { method: 'DELETE' });
            } finally {
                this.loading = false;
            }
        },

        async win(id) {
            const response = await v1(`opportunities/${id}/win`, { method: 'POST' });

            return response?.data ?? response;
        },
    },
});
