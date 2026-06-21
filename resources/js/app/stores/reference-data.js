import { defineStore } from 'pinia';

import { v1 } from '../utils/api';

export const useReferenceDataStore = defineStore('reference-data', {
    state: () => ({
        rows: [],
        meta: {
            current_page: 1,
            last_page: 1,
            per_page: 15,
            total: 0,
        },
        options: {
            types: [],
        },
        loading: false,
    }),
    actions: {
        async fetch({ page = 1, perPage = 15, type = '', search = '' } = {}) {
            this.loading = true;

            try {
                const params = new URLSearchParams({ page, per_page: perPage });

                if (type) params.set('type', type);
                if (search) params.set('search', search);

                const response = await v1(`reference-data?${params}`);

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
                const response = await v1('reference-data', { method: 'POST', body: payload });

                return response?.data ?? response;
            } finally {
                this.loading = false;
            }
        },

        async update(optionId, payload) {
            this.loading = true;

            try {
                const response = await v1(`reference-data/${optionId}`, { method: 'PATCH', body: payload });

                return response?.data ?? response;
            } finally {
                this.loading = false;
            }
        },

        async remove(optionId) {
            this.loading = true;

            try {
                return await v1(`reference-data/${optionId}`, { method: 'DELETE' });
            } finally {
                this.loading = false;
            }
        },
    },
});
