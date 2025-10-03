import { RuleTester } from '@typescript-eslint/rule-tester';
import * as vitest from 'vitest';

import { rule, RULE_NAME } from './enforce-typescript-in-app-code.js';

RuleTester.afterAll = vitest.afterAll;
RuleTester.it = vitest.it;
RuleTester.itOnly = vitest.it.only;
RuleTester.describe = vitest.describe;

const ruleTester = new RuleTester();

ruleTester.run(RULE_NAME, rule, {
  valid: [
    // TypeScript files are allowed in common.
    {
      filename: '/Users/dev/RAWeb/resources/js/common/utils/sortAchievements.ts',
      code: `export function sortAchievements() {}`,
    },
    // TypeScript files are allowed in shared.
    {
      filename: '/Users/dev/RAWeb/resources/js/shared/components/Header.tsx',
      code: `export const Header = () => null;`,
    },
    // TypeScript files are allowed in features.
    {
      filename:
        '/Users/dev/RAWeb/resources/js/features/forums/components/ForumPostCard/ForumPostCard.tsx',
      code: `export const ForumPostCard = () => null;`,
    },
    // TypeScript files are allowed in pages.
    {
      filename: '/Users/dev/RAWeb/resources/js/pages/Home.tsx',
      code: `export const Home = () => null;`,
    },
    // TypeScript files are allowed in test.
    {
      filename: '/Users/dev/RAWeb/resources/js/test/factories/createGame.ts',
      code: `export function createGame() {}`,
    },
    // TypeScript files are allowed in core.
    {
      filename: '/Users/dev/RAWeb/resources/js/core/hooks/useAuth.ts',
      code: `export function useAuth() {}`,
    },
    // JavaScript files are allowed in tools.
    {
      filename: '/Users/dev/RAWeb/resources/js/tools/crowdin-download.js',
      code: `export function crowdinDownload() {}`,
    },
    // JavaScript files are allowed in tall-stack.
    {
      filename: '/Users/dev/RAWeb/resources/js/tall-stack/utils/helpers.js',
      code: `export function helper() {}`,
    },
    // JavaScript files are allowed in types.
    {
      filename: '/Users/dev/RAWeb/resources/js/types/index.js',
      code: `export const types = {};`,
    },
    // Root-level JavaScript files are allowed.
    {
      filename: '/Users/dev/RAWeb/resources/js/app.js',
      code: `import React from 'react';`,
    },
    // Root-level JavaScript files are allowed.
    {
      filename: '/Users/dev/RAWeb/resources/js/ssr.js',
      code: `import React from 'react';`,
    },
  ],
  invalid: [
    // JavaScript files are not allowed in common.
    {
      filename: '/Users/dev/RAWeb/resources/js/common/utils/sortAchievements.js',
      code: `export function sortAchievements() {}`,
      errors: [
        {
          messageId: 'useTypeScript',
          data: {
            directory: 'common',
          },
        },
      ],
    },
    // JavaScript files are not allowed in shared.
    {
      filename: '/Users/dev/RAWeb/resources/js/shared/components/Header.js',
      code: `export const Header = () => null;`,
      errors: [
        {
          messageId: 'useTypeScript',
          data: {
            directory: 'shared',
          },
        },
      ],
    },
    // JavaScript files are not allowed in features.
    {
      filename:
        '/Users/dev/RAWeb/resources/js/features/forums/components/ForumPostCard/ForumPostCard.js',
      code: `export const ForumPostCard = () => null;`,
      errors: [
        {
          messageId: 'useTypeScript',
          data: {
            directory: 'features',
          },
        },
      ],
    },
    // JavaScript files are not allowed in pages.
    {
      filename: '/Users/dev/RAWeb/resources/js/pages/Home.js',
      code: `export const Home = () => null;`,
      errors: [
        {
          messageId: 'useTypeScript',
          data: {
            directory: 'pages',
          },
        },
      ],
    },
    // JavaScript files are not allowed in test.
    {
      filename: '/Users/dev/RAWeb/resources/js/test/factories/createGame.js',
      code: `export function createGame() {}`,
      errors: [
        {
          messageId: 'useTypeScript',
          data: {
            directory: 'test',
          },
        },
      ],
    },
    // JavaScript files are not allowed in core.
    {
      filename: '/Users/dev/RAWeb/resources/js/core/hooks/useAuth.js',
      code: `export function useAuth() {}`,
      errors: [
        {
          messageId: 'useTypeScript',
          data: {
            directory: 'core',
          },
        },
      ],
    },
  ],
});
