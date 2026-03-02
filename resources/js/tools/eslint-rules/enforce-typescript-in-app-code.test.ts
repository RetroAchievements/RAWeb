import { describe, expect, it, vi } from 'vitest';

import { rule } from './enforce-typescript-in-app-code.js';

function runRule(filename: string) {
  const report = vi.fn();
  const context = { filename, report };
  const visitors = rule.create(context as any);

  // Simulate the Program visitor if it exists.
  if (visitors.Program) {
    visitors.Program({});
  }

  return report;
}

describe('Rule: enforce-typescript-in-app-code', () => {
  describe('valid cases', () => {
    it('allows TypeScript files in common', () => {
      const report = runRule('/Users/dev/RAWeb/resources/js/common/utils/sortAchievements.ts');
      expect(report).not.toHaveBeenCalled();
    });

    it('allows TypeScript files in shared', () => {
      const report = runRule('/Users/dev/RAWeb/resources/js/shared/components/Header.tsx');
      expect(report).not.toHaveBeenCalled();
    });

    it('allows TypeScript files in features', () => {
      const report = runRule(
        '/Users/dev/RAWeb/resources/js/features/forums/components/ForumPostCard/ForumPostCard.tsx',
      );
      expect(report).not.toHaveBeenCalled();
    });

    it('allows TypeScript files in pages', () => {
      const report = runRule('/Users/dev/RAWeb/resources/js/pages/Home.tsx');
      expect(report).not.toHaveBeenCalled();
    });

    it('allows TypeScript files in test', () => {
      const report = runRule('/Users/dev/RAWeb/resources/js/test/factories/createGame.ts');
      expect(report).not.toHaveBeenCalled();
    });

    it('allows TypeScript files in core', () => {
      const report = runRule('/Users/dev/RAWeb/resources/js/core/hooks/useAuth.ts');
      expect(report).not.toHaveBeenCalled();
    });

    it('allows JavaScript files in tools', () => {
      const report = runRule('/Users/dev/RAWeb/resources/js/tools/crowdin-download.js');
      expect(report).not.toHaveBeenCalled();
    });

    it('allows JavaScript files in tall-stack', () => {
      const report = runRule('/Users/dev/RAWeb/resources/js/tall-stack/utils/helpers.js');
      expect(report).not.toHaveBeenCalled();
    });

    it('allows JavaScript files in types', () => {
      const report = runRule('/Users/dev/RAWeb/resources/js/types/index.js');
      expect(report).not.toHaveBeenCalled();
    });

    it('allows root-level JavaScript files', () => {
      const report = runRule('/Users/dev/RAWeb/resources/js/app.js');
      expect(report).not.toHaveBeenCalled();
    });
  });

  describe('invalid cases', () => {
    it('reports JavaScript files in common', () => {
      const report = runRule('/Users/dev/RAWeb/resources/js/common/utils/sortAchievements.js');
      expect(report).toHaveBeenCalledWith(
        expect.objectContaining({
          messageId: 'useTypeScript',
          data: { directory: 'common' },
        }),
      );
    });

    it('reports JavaScript files in shared', () => {
      const report = runRule('/Users/dev/RAWeb/resources/js/shared/components/Header.js');
      expect(report).toHaveBeenCalledWith(
        expect.objectContaining({
          messageId: 'useTypeScript',
          data: { directory: 'shared' },
        }),
      );
    });

    it('reports JavaScript files in features', () => {
      const report = runRule(
        '/Users/dev/RAWeb/resources/js/features/forums/components/ForumPostCard/ForumPostCard.js',
      );
      expect(report).toHaveBeenCalledWith(
        expect.objectContaining({
          messageId: 'useTypeScript',
          data: { directory: 'features' },
        }),
      );
    });

    it('reports JavaScript files in pages', () => {
      const report = runRule('/Users/dev/RAWeb/resources/js/pages/Home.js');
      expect(report).toHaveBeenCalledWith(
        expect.objectContaining({
          messageId: 'useTypeScript',
          data: { directory: 'pages' },
        }),
      );
    });

    it('reports JavaScript files in test', () => {
      const report = runRule('/Users/dev/RAWeb/resources/js/test/factories/createGame.js');
      expect(report).toHaveBeenCalledWith(
        expect.objectContaining({
          messageId: 'useTypeScript',
          data: { directory: 'test' },
        }),
      );
    });

    it('reports JavaScript files in core', () => {
      const report = runRule('/Users/dev/RAWeb/resources/js/core/hooks/useAuth.js');
      expect(report).toHaveBeenCalledWith(
        expect.objectContaining({
          messageId: 'useTypeScript',
          data: { directory: 'core' },
        }),
      );
    });
  });
});
