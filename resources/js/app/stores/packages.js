import { defineStore } from 'pinia';

import { v1 } from '../utils/api';

export const usePackagesStore = defineStore('packages', {
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
            billing_intervals: [],
            default_currency: '',
            products: [],
        },
        loading: false,
    }),
    actions: {
        async fetch({ page = 1, perPage = 10, search = '', status = '', productId = '' } = {}) {
            this.loading = true;

            try {
                const params = new URLSearchParams({ page, per_page: perPage });

                if (search) params.set('search', search);
                if (status) params.set('status', status);
                if (productId) params.set('product_id', productId);

                const response = await v1(`packages?${params}`);

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
                const response = await v1('packages', { method: 'POST', body: payload });

                return response?.data ?? response;
            } finally {
                this.loading = false;
            }
        },

        async update(packageId, payload) {
            this.loading = true;

            try {
                const response = await v1(`packages/${packageId}`, { method: 'PATCH', body: payload });

                return response?.data ?? response;
            } finally {
                this.loading = false;
            }
        },

        async archive(packageId) {
            this.loading = true;

            try {
                return await v1(`packages/${packageId}`, { method: 'DELETE' });
            } finally {
                this.loading = false;
            }
        },
    },
});
