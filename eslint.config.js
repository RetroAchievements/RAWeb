import eslint from '@eslint/js';
import tanstackQuery from '@tanstack/eslint-plugin-query';
import vitest from '@vitest/eslint-plugin';
import prettierConfig from 'eslint-config-prettier';
import importPlugin from 'eslint-plugin-import';
import jsxA11y from 'eslint-plugin-jsx-a11y';
import prettier from 'eslint-plugin-prettier';
import react from 'eslint-plugin-react';
import reactHooks from 'eslint-plugin-react-hooks';
import simpleImportSort from 'eslint-plugin-simple-import-sort';
import sortKeysShorthand from 'eslint-plugin-sort-keys-shorthand';
import testingLibrary from 'eslint-plugin-testing-library';
import unicorn from 'eslint-plugin-unicorn';
import globals from 'globals';
import tseslint from 'typescript-eslint';

export default tseslint.config(
  {
    ignores: [
      '**/generated.d.ts',
      '**/ziggy.d.ts',
      '**/ziggy.js',
      'dist/**',
      'node_modules/**',
      'vendor/**',
      'public/build/**',
      'public/hot/**',
      'storage/**',
      'bootstrap/cache/**',
    ],
  },

  eslint.configs.recommended,
  ...tseslint.configs.recommended,
  prettierConfig,

  // Use native flat configs where available.
  importPlugin.flatConfigs.recommended,
  jsxA11y.flatConfigs.recommended,

  {
    languageOptions: {
      parser: tseslint.parser,
      ecmaVersion: 'latest',
      sourceType: 'module',
      globals: {
        ...globals.browser,
        Atomics: 'readonly',
        SharedArrayBuffer: 'readonly',
      },
    },

    plugins: {
      '@typescript-eslint': tseslint.plugin,
      react,
      'react-hooks': reactHooks,
      prettier,
      'simple-import-sort': simpleImportSort,
      unicorn,
      vitest,
      '@tanstack/query': tanstackQuery,
      'sort-keys-shorthand': sortKeysShorthand,
    },

    settings: {
      react: {
        version: 'detect',
      },
      'import/resolver': {
        typescript: {}, // Enables TypeScript path aliases from tsconfig.json.
      },
    },

    rules: {
      'no-unused-vars': 'off', // @typescript-eslint/no-unused-vars handles this better.
      'max-len': 'off',
      'prefer-destructuring': 'off',
      'no-undef': 'off', // TypeScript handles undefined variables.
      'no-eval': 'off', // Legacy code uses eval in some places.
      'no-console': 'off', // Console is allowed for debugging.
      'no-plusplus': 'off',
      'comma-dangle': 'off',
      eqeqeq: 'error',
      'func-names': 'off',
      'no-alert': 'off', // Still using confirm() dialogs in some places.
      'no-else-return': 'error',
      'no-loop-func': 'off',
      'no-param-reassign': 'off',
      'no-restricted-globals': 'off', // Allows confirm() and other browser globals.
      'no-return-await': 'error',
      'no-shadow': 'off', // TypeScript's no-shadow handles this better.
      'no-use-before-define': 'off',
      'no-useless-escape': 'off', // False positives with regex patterns.
      'no-var': 'off', // Legacy jQuery code still uses var.
      'object-shorthand': 'error',
      'prefer-arrow-callback': 'off',
      'prefer-rest-params': 'off',
      'prefer-template': 'off',
      'vars-on-top': 'off',
      'global-require': 'off',
      'newline-before-return': 'error',
      camelcase: 'off',
      'no-constant-condition': ['warn', { checkLoops: true }],

      '@typescript-eslint/consistent-type-imports': 'error',
      '@typescript-eslint/no-unused-vars': [
        'error',
        {
          argsIgnorePattern: '^_',
          varsIgnorePattern: '^_',
          caughtErrorsIgnorePattern: '^_',
        },
      ],
      '@typescript-eslint/no-explicit-any': 'warn',
      '@typescript-eslint/no-var-requires': 'error',
      '@typescript-eslint/ban-ts-comment': 'warn',

      ...react.configs.recommended.rules,
      ...reactHooks.configs['recommended-latest'].rules,
      'react/display-name': 'off',
      'react/jsx-no-literals': 'error', // Enforces i18n by preventing hardcoded text.
      'react/jsx-no-target-blank': 'off', // We don't support browsers old enough to need this.
      'react/no-unescaped-entities': 'off',
      'react/prop-types': 'off',
      'react/react-in-jsx-scope': 'off', // React 17+ doesn't require React in scope.

      // Override import plugin rules that were set by flatConfigs.recommended.
      'import/first': 'error',
      'import/newline-after-import': 'error',
      'import/no-duplicates': 'error',
      'import/no-unresolved': [
        'error',
        {
          ignore: ['^.*/vendor/.*'], // CI can't see these files.
        },
      ],
      'simple-import-sort/exports': 'error',
      'simple-import-sort/imports': 'error',

      'unicorn/filename-case': 'off',
      'unicorn/no-array-callback-reference': 'off',
      'unicorn/no-array-for-each': 'warn',
      'unicorn/no-array-reduce': 'error',
      'unicorn/no-null': 'off',
      'unicorn/prefer-includes': 'error',
      'unicorn/prefer-module': 'off',
      'unicorn/prefer-node-protocol': 'off',
      'unicorn/prefer-switch': 'off',

      ...tanstackQuery.configs.recommended.rules,

      'prettier/prettier': [
        'error',
        {
          tabWidth: 2,
          printWidth: 100,
          singleQuote: true,
          tailwindAttributes: [
            'anchorClassName',
            'baseCommandListClassName',
            'containerClassName',
            'gameTitleClassName',
            'imgClassName',
            'wrapperClassName',
          ],
          plugins: ['prettier-plugin-tailwindcss'],
        },
      ],

      'no-restricted-imports': [
        'error',
        {
          paths: [
            {
              name: '@inertiajs/react',
              importNames: ['Head'],
              message: 'Use @/common/components/SEO instead.',
            },
            {
              name: '@inertiajs/react',
              importNames: ['Link'],
              message: 'Use @/common/components/InertiaLink instead.',
            },
          ],
          patterns: [
            '@testing-library/react', // Import from @/test instead.
            '@radix-ui/*', // Use our wrapped Base* components.
            'sonner', // Use toastMessage instead.
            'vaul', // Use our wrapped BaseDrawer component.
          ],
        },
      ],
    },
  },

  {
    files: ['**/*.test.ts', '**/*.test.tsx', '**/generated.d.ts'],
    ...testingLibrary.configs['flat/react'],
    rules: {
      ...testingLibrary.configs['flat/react'].rules,
      '@typescript-eslint/no-explicit-any': 'off',
      'testing-library/no-dom-import': 'off',
      'testing-library/no-render-in-lifecycle': 'off',
      'react/jsx-no-literals': 'off',
    },
  },
);
