import { defineStore } from 'pinia';

import { v1 } from '../utils/api';

export const useFinanceStore = defineStore('finance', {
    state: () => ({
        metrics: {
            mrr: 0,
            arr: 0,
            outstanding_total: 0,
            overdue_total: 0,
            overdue_count: 0,
            payments_this_month: 0,
            active_contract_value: 0,
            renewals_due_soon: 0,
        },
        loading: false,
    }),
    actions: {
        async fetchMetrics() {
            this.loading = true;

            try {
                const response = await v1('dashboard/finance');

                this.metrics = response?.data ?? this.metrics;

                return this.metrics;
            } finally {
                this.loading = false;
            }
        },
    },
});
