import { defineStore } from 'pinia';

import { v1 } from '../utils/api';

export const useProvisioningTemplatesStore = defineStore('provisioning-templates', {
    state: () => ({
        rows: [],
        meta: { current_page: 1, last_page: 1, per_page: 10, total: 0 },
        loading: false,
    }),
    actions: {
        async fetch({ page = 1, perPage = 10, search = '' } = {}) {
            this.loading = true;

            try {
                const params = new URLSearchParams({ page, per_page: perPage });
                if (search) params.set('search', search);

                const response = await v1(`provisioning-templates?${params}`);

                this.rows = response?.data?.map((item) => item?.data ?? item) ?? [];
                this.meta = response?.meta ?? this.meta;

                return response;
            } finally {
                this.loading = false;
            }
        },

        async create(payload) {
            this.loading = true;

            try {
                return await v1('provisioning-templates', { method: 'POST', body: payload });
            } finally {
                this.loading = false;
            }
        },

        async update(templateId, payload) {
            this.loading = true;

            try {
                return await v1(`provisioning-templates/${templateId}`, { method: 'PATCH', body: payload });
            } finally {
                this.loading = false;
            }
        },
    },
});
