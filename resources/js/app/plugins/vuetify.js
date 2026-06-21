import 'vuetify/styles';

import { createVuetify } from 'vuetify';

export const vuetify = createVuetify({
    theme: {
        defaultTheme: 'rw',
        themes: {
            rw: {
                dark: false,
                colors: {
                    background: '#f7f8fa',
                    surface:    '#ffffff',
                    primary:    '#2563eb',
                    secondary:  '#475569',
                    accent:     '#38bdf8',
                    error:      '#dc2626',
                    info:       '#0284c7',
                    success:    '#16a34a',
                    warning:    '#d97706',
                    'on-primary': '#ffffff',
                },
            },
        },
    },
    defaults: {
        VBtn: {
            rounded: 'lg',
            fontWeight: '600',
        },
        VCard: {
            rounded: 'xl',
            elevation: 0,
        },
        VTextField: {
            variant: 'outlined',
            density: 'comfortable',
            color: 'primary',
        },
        VSelect: {
            variant: 'outlined',
            density: 'comfortable',
            color: 'primary',
        },
        VCombobox: {
            variant: 'outlined',
            density: 'comfortable',
            color: 'primary',
        },
        VChip: {
            rounded: 'md',
        },
    },
});
