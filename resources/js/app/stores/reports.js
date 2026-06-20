import { defineStore } from 'pinia';

import { api, v1 } from '../utils/api';

export const useReportsStore = defineStore('reports', {
    state: () => ({
        types: [],
        report: null,
        loading: false,
    }),
    actions: {
        async fetchTypes() {
            this.loading = true;

            try {
                const response = await v1('reports');
                this.types = response?.data ?? [];

                return this.types;
            } finally {
                this.loading = false;
            }
        },

        async fetchReport(type) {
            this.loading = true;

            try {
                const response = await v1(`reports/${type}`);
                this.report = response?.data ?? null;

                return this.report;
            } finally {
                this.loading = false;
            }
        },

        async exportReport(type) {
            const blob = await api(`/api/v1/reports/${type}/export`, { responseType: 'blob' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');

            link.href = url;
            link.download = `${type}-${new Date().toISOString().slice(0, 10)}.xlsx`;
            document.body.appendChild(link);
            link.click();
            link.remove();
            URL.revokeObjectURL(url);
        },
    },
});
