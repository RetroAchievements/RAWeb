import { ESLintUtils } from '@typescript-eslint/utils';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);

export const RULE_NAME = 'enforce-typescript-in-app-code';

export const rule = ESLintUtils.RuleCreator(() => __filename)({
  name: RULE_NAME,

  meta: {
    type: 'problem',
    docs: {
      description:
        'Enforces TypeScript-only code in application directories (core, common, shared, features, pages, test). JavaScript files are only allowed in tools/, tall-stack/, and root-level config files.',
    },
    schema: [],
    messages: {
      useTypeScript:
        'JavaScript files are not allowed in {{directory}}. Please use TypeScript (.ts or .tsx) instead. JavaScript is only permitted in tools/, tall-stack/, and root-level config files.',
    },
  },

  defaultOptions: [],

  create(context) {
    // Only run on .js files (not .ts, .tsx, .d.ts).
    const filename = context.filename || context.getFilename();
    if (!filename.endsWith('.js')) {
      return {};
    }

    const normalizedFilename = filename.replace(/\\/g, '/');

    // Check if the file is in resources/js.
    if (!normalizedFilename.includes('/resources/js/')) {
      return {};
    }

    // Extract the path after resources/js/.
    const segments = normalizedFilename.split('/');
    const resourcesIndex = segments.indexOf('resources');

    if (resourcesIndex === -1 || segments[resourcesIndex + 1] !== 'js') {
      return {};
    }

    const moduleSegment = segments[resourcesIndex + 2];

    // Directories that require TypeScript.
    const typescriptOnlyDirs = ['core', 'common', 'shared', 'features', 'pages', 'test'];

    if (typescriptOnlyDirs.includes(moduleSegment)) {
      return {
        Program(node) {
          context.report({
            node,
            messageId: 'useTypeScript',
            data: {
              directory: moduleSegment,
            },
          });
        },
      };
    }

    return {};
  },
});
