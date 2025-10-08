import pluginVue from 'eslint-plugin-vue';
import globals from 'globals';
import prettier from 'eslint-config-prettier/flat';

export default [
    ...pluginVue.configs['flat/recommended'],
    {
        ignores: [
            'vendor',
            'node_modules',
            'public',
            'legacy-resources',
            'tailwind.config.js',
            'resources/js/components/ui/*',
        ],
    },
    {
        rules: {
            'vue/block-order': [
                'error',
                {
                    order: ['script', 'template', 'style'],
                },
            ],
            'vue/multi-word-component-names': 'off',
            'vue/require-default-prop': 'off',
            'vue/require-prop-types': 'off',
            'vue/no-v-html': 'off',
            'vue/component-api-style': ['error', ['script-setup']],
            'vue/component-name-in-template-casing': 'error',
            'vue/no-console': 'error',
            semi: ['error'],
        },
        languageOptions: {
            sourceType: 'module',
            globals: {
                ...globals.browser,
            },
        },
    },
    prettier,
];
