import { defineStore } from 'pinia';

import { v1 } from '../utils/api';

export const useDeploymentsStore = defineStore('deployments', {
    state: () => ({
        rows: [],
        meta: { current_page: 1, last_page: 1, per_page: 10, total: 0 },
        options: { statuses: [], environments: [], clients: [], products: [], packages: [] },
        loading: false,
    }),
    actions: {
        async fetch({ page = 1, perPage = 10, search = '', status = '', environment = '' } = {}) {
            this.loading = true;

            try {
                const params = new URLSearchParams({ page, per_page: perPage });
                if (search) params.set('search', search);
                if (status) params.set('status', status);
                if (environment) params.set('environment', environment);

                const response = await v1(`deployments?${params}`);

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
