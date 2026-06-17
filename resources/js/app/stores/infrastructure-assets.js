import { defineStore } from 'pinia';

import { v1 } from '../utils/api';

export const useInfrastructureAssetsStore = defineStore('infrastructure-assets', {
    state: () => ({
        rows: [],
        meta: { current_page: 1, last_page: 1, per_page: 10, total: 0 },
        options: { types: [], default_currency: '', deployments: [] },
        loading: false,
    }),
    actions: {
        async fetch({ page = 1, perPage = 10, search = '', type = '' } = {}) {
            this.loading = true;

            try {
                const params = new URLSearchParams({ page, per_page: perPage });
                if (search) params.set('search', search);
                if (type) params.set('type', type);

                const response = await v1(`infrastructure-assets?${params}`);

                this.rows = response?.data?.map((item) => item?.data ?? item) ?? [];
                this.meta = response?.meta ?? this.meta;
                this.options = response?.options ?? this.options;

                return response;
            } finally {
                this.loading = false;
            }
        },
    },
});
