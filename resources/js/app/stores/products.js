import { defineStore } from 'pinia';

import { v1 } from '../utils/api';

export const useProductsStore = defineStore('products', {
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

                const response = await v1(`products?${params}`);

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
                const response = await v1('products', { method: 'POST', body: payload });

                return response?.data ?? response;
            } finally {
                this.loading = false;
            }
        },

        async update(productId, payload) {
            this.loading = true;

            try {
                const response = await v1(`products/${productId}`, { method: 'PATCH', body: payload });

                return response?.data ?? response;
            } finally {
                this.loading = false;
            }
        },

        async archive(productId) {
            this.loading = true;

            try {
                return await v1(`products/${productId}`, { method: 'DELETE' });
            } finally {
                this.loading = false;
            }
        },
    },
});
