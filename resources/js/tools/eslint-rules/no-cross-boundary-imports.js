import { ESLintUtils } from '@typescript-eslint/utils';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);

export const RULE_NAME = 'no-cross-boundary-imports';

export const rule = ESLintUtils.RuleCreator(() => __filename)({
  name: RULE_NAME,

  meta: {
    type: 'problem',
    docs: {
      description: `Enforces architectural boundaries between core, common, shared, and feature modules in resources/js.

Architecture layers (from most to least restrictive):
- core: Foundation layer - global providers, atoms, models, hooks, etc, which all or most features rely on
- common: Reusable utilities and helpers, usually winds up being split into core+shared after the project reaches a certain scale
- shared: Cross-cutting UI components and hooks that may not necessarily be global
- feature: Feature-specific code
- tall-stack: TALL stack utilities, deprecated
- types: Type definitions, deprecated (put global types in core/common/shared)
- tools: Development tools (cannot be imported by application code)

Import rules:
- core → can only import from core
- common → can import from core, common
- shared → can import from core, common, shared
- feature → can import from core, common, shared, and the same feature
- No module can import from a different feature
- No module can import from tools`,
    },
    hasSuggestions: true,
    schema: [],
    messages: {
      crossBoundaryImport:
        '{{source}} modules cannot import from {{target}} modules. Consider lifting the imported code to common/core/shared modules to make it accessible.',
      crossFeatureImport:
        'Features should not import from other features ({{from}} → {{to}}). Doing so can create a circular dependency which bloats the JS bundle and can be difficult to untangle. If two different features need to share the same component, util, model, etc, consider lifting it to common/core/shared.',
      toolsImport:
        'Application code cannot import from tools/. Tools are for development use only.',
      refactorSuggestion:
        'Move this code to {{suggestedLocation}} to resolve the architectural violation.',
    },
  },

  defaultOptions: [],

  create(context) {
    /**
     * @param {string} importPath
     * @returns {{ type: string; feature?: string } | null}
     */
    function getModuleType(importPath) {
      // Handle both @/ alias and relative paths that resolve to resources/js.
      let normalizedPath = importPath;

      // Convert @/ alias to resources/js/ for easier parsing.
      if (importPath.startsWith('@/')) {
        normalizedPath = importPath.replace('@/', 'resources/js/');
      }

      // Check for tools imports.
      if (normalizedPath.includes('/tools/') || normalizedPath.startsWith('resources/js/tools/')) {
        return { type: 'tools' };
      }

      // Check for tall-stack imports (allowed by all).
      if (
        normalizedPath.includes('/tall-stack/') ||
        normalizedPath.startsWith('resources/js/tall-stack/')
      ) {
        return null; // Allowed by all, so we return null to skip checks.
      }

      // Check for types imports (allowed by all).
      if (normalizedPath.includes('/types/') || normalizedPath.startsWith('resources/js/types/')) {
        return null; // Allowed by all, so we return null to skip checks.
      }

      // Parse the module type from the path.
      const segments = normalizedPath.split('/');

      // Find resources/js in the path.
      const resourcesIndex = segments.indexOf('resources');
      if (resourcesIndex !== -1 && segments[resourcesIndex + 1] === 'js') {
        const moduleSegment = segments[resourcesIndex + 2];

        if (['core', 'common', 'shared'].includes(moduleSegment)) {
          return { type: moduleSegment };
        }

        if (moduleSegment === 'features' && segments.length > resourcesIndex + 3) {
          return { type: 'feature', feature: segments[resourcesIndex + 3] };
        }
      }

      return null;
    }

    /**
     * @param {string} filename
     * @returns {{ type: string; feature?: string } | null}
     */
    function getCurrentModuleType(filename) {
      // Normalize the filename to use forward slashes.
      const normalizedFilename = filename.replace(/\\/g, '/');

      // Check if the current file is in resources/js.
      if (!normalizedFilename.includes('/resources/js/')) {
        return null;
      }

      const segments = normalizedFilename.split('/');
      const resourcesIndex = segments.indexOf('resources');

      if (resourcesIndex !== -1 && segments[resourcesIndex + 1] === 'js') {
        const moduleSegment = segments[resourcesIndex + 2];

        if (['core', 'common', 'shared'].includes(moduleSegment)) {
          return { type: moduleSegment };
        }

        if (moduleSegment === 'features' && segments.length > resourcesIndex + 3) {
          return { type: 'feature', feature: segments[resourcesIndex + 3] };
        }
      }

      return null;
    }

    return {
      ImportDeclaration(node) {
        const importPath = node.source.value;

        if (typeof importPath !== 'string') {
          return;
        }

        const currentModule = getCurrentModuleType(context.filename);
        if (!currentModule) {
          // Current file is not in one of our target modules.
          return;
        }

        let importedModule = getModuleType(importPath);

        // If it's a relative import, resolve it to get the actual module.
        if (importPath.startsWith('.')) {
          const currentDir = path.dirname(context.filename);
          const resolvedPath = path.resolve(currentDir, importPath);
          const normalizedPath = resolvedPath.replace(/\\/g, '/');

          // Convert the resolved path to a module type.
          importedModule = getCurrentModuleType(normalizedPath);
        }

        if (!importedModule) {
          // Import is not from one of our target modules, or is from tall-stack/types (allowed).
          return;
        }

        // Check for tools imports.
        if (importedModule.type === 'tools') {
          context.report({
            node: node.source,
            messageId: 'toolsImport',
          });

          return;
        }

        const { type: currentType, feature: currentFeature } = currentModule;
        const { type: importedType, feature: importedFeature } = importedModule;

        // Rule 1: core can only import from core.
        if (currentType === 'core' && importedType !== 'core') {
          context.report({
            node: node.source,
            messageId: 'crossBoundaryImport',
            data: {
              source: currentType,
              target: importedType,
            },
            suggest: [
              {
                messageId: 'refactorSuggestion',
                data: { suggestedLocation: 'core' },
                fix: () => null, // No automatic fix, just suggestion.
              },
            ],
          });
        }

        // Rule 2: common can import from core and common.
        if (currentType === 'common' && !['core', 'common'].includes(importedType)) {
          context.report({
            node: node.source,
            messageId: 'crossBoundaryImport',
            data: {
              source: currentType,
              target: importedType,
            },
            suggest: [
              {
                messageId: 'refactorSuggestion',
                data: { suggestedLocation: 'core or common' },
                fix: () => null, // No automatic fix, just suggestion.
              },
            ],
          });
        }

        // Rule 3: shared can import from core, common, and shared.
        if (currentType === 'shared' && !['core', 'common', 'shared'].includes(importedType)) {
          context.report({
            node: node.source,
            messageId: 'crossBoundaryImport',
            data: {
              source: currentType,
              target: importedType,
            },
            suggest: [
              {
                messageId: 'refactorSuggestion',
                data: { suggestedLocation: 'core, common, or shared' },
                fix: () => null, // No automatic fix, just suggestion.
              },
            ],
          });
        }

        // Rule 4: feature cannot import from another feature.
        if (
          currentType === 'feature' &&
          importedType === 'feature' &&
          currentFeature !== importedFeature
        ) {
          context.report({
            node: node.source,
            messageId: 'crossFeatureImport',
            data: {
              from: `feature/${currentFeature}`,
              to: `feature/${importedFeature}`,
            },
            suggest: [
              {
                messageId: 'refactorSuggestion',
                data: {
                  suggestedLocation:
                    'shared (for UI components), common (for utilities), or core (for business logic)',
                },
                fix: () => null, // No automatic fix, just suggestion.
              },
            ],
          });
        }
      },
    };
  },
});
