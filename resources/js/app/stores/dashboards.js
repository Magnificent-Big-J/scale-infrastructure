import { defineStore } from 'pinia';

import { v1 } from '../utils/api';

export const useDashboardsStore = defineStore('dashboards', {
    state: () => ({
        executive: {
            active_clients: 0,
            at_risk_clients: 0,
            active_deployments: 0,
            mrr: 0,
            arr: 0,
            outstanding_total: 0,
            open_tickets: 0,
            open_incidents: 0,
            average_client_health: 0,
        },
        operations: {
            deployments_total: 0,
            deployments_by_status: {},
            active_deployments: 0,
            degraded_deployments: 0,
            monitoring_checks_total: 0,
            monitoring_by_status: {},
            failing_checks: 0,
            warning_checks: 0,
            infrastructure_assets: 0,
            open_incidents: 0,
        },
        loading: false,
    }),
    actions: {
        async fetchExecutive() {
            this.loading = true;

            try {
                const response = await v1('dashboard/executive');
                this.executive = response?.data ?? this.executive;

                return this.executive;
            } finally {
                this.loading = false;
            }
        },

        async fetchOperations() {
            this.loading = true;

            try {
                const response = await v1('dashboard/operations');
                this.operations = response?.data ?? this.operations;

                return this.operations;
            } finally {
                this.loading = false;
            }
        },
    },
});
