import pluginVue from 'eslint-plugin-vue';

export default [
    // 'essential' only: correctness rules (unused refs, missing v-for keys,
    // side effects in computed, etc.), not the stylistic/formatting rule
    // category from 'recommended' - this codebase's existing convention is
    // compact single-line templates, and 'recommended' would want to rewrite
    // the whitespace of nearly every template in the app to a
    // multi-line-per-attribute style nobody asked for.
    ...pluginVue.configs['flat/essential'],
    {
        ignores: ['public/**', 'vendor/**', 'node_modules/**'],
    },
    {
        languageOptions: {
            ecmaVersion: 2022,
            sourceType: 'module',
            globals: {
                window: 'readonly',
                document: 'readonly',
                navigator: 'readonly',
                console: 'readonly',
                fetch: 'readonly',
                localStorage: 'readonly',
                sessionStorage: 'readonly',
            },
        },
        rules: {
            'vue/multi-word-component-names': 'off',
            'vue/no-v-html': 'off',
            'no-unused-vars': ['warn', { argsIgnorePattern: '^_', caughtErrorsIgnorePattern: '^_' }],
        },
    },
];
