import { describe, expect, it, vi } from 'vitest';

import { rule } from './no-cross-boundary-imports.js';

function runRule(filename: string, importSource: string) {
  const report = vi.fn();
  const context = { filename, report };
  const visitors = rule.create(context as any);

  // Simulate an ImportDeclaration node.
  const node = {
    source: { value: importSource },
  };
  visitors.ImportDeclaration(node);

  return report;
}

describe('Rule: no-cross-boundary-imports', () => {
  describe('valid cases', () => {
    it('allows common importing from common', () => {
      const report = runRule(
        '/Users/dev/RAWeb/resources/js/common/utils/sortAchievements.ts',
        '@/common/utils/getIsInteractiveElement',
      );
      expect(report).not.toHaveBeenCalled();
    });

    it('allows common importing from common via relative path', () => {
      const report = runRule(
        '/Users/dev/RAWeb/resources/js/common/utils/sortAchievements.ts',
        './getIsInteractiveElement',
      );
      expect(report).not.toHaveBeenCalled();
    });

    it('allows shared importing from common', () => {
      const report = runRule(
        '/Users/dev/RAWeb/resources/js/shared/components/Layout.tsx',
        '@/common/utils/l10n/formatDate',
      );
      expect(report).not.toHaveBeenCalled();
    });

    it('allows shared importing from shared', () => {
      const report = runRule(
        '/Users/dev/RAWeb/resources/js/shared/components/Layout.tsx',
        '@/shared/components/Header',
      );
      expect(report).not.toHaveBeenCalled();
    });

    it('allows feature importing from common', () => {
      const report = runRule(
        '/Users/dev/RAWeb/resources/js/features/games/components/GameList.tsx',
        '@/common/utils/sortAchievements',
      );
      expect(report).not.toHaveBeenCalled();
    });

    it('allows feature importing from shared', () => {
      const report = runRule(
        '/Users/dev/RAWeb/resources/js/features/games/components/GameList.tsx',
        '@/shared/components/DataTable',
      );
      expect(report).not.toHaveBeenCalled();
    });

    it('allows feature importing from the same feature', () => {
      const report = runRule(
        '/Users/dev/RAWeb/resources/js/features/forums/components/ForumPostCard/ForumPostCard.tsx',
        '@/features/forums/components/ForumPostCard/ForumPostCardMeta',
      );
      expect(report).not.toHaveBeenCalled();
    });

    it('allows imports from tall-stack', () => {
      const report = runRule(
        '/Users/dev/RAWeb/resources/js/common/hooks/useCardTooltip.ts',
        '@/tall-stack/components/Button',
      );
      expect(report).not.toHaveBeenCalled();
    });

    it('allows imports from types', () => {
      const report = runRule(
        '/Users/dev/RAWeb/resources/js/features/games/components/GameList.tsx',
        '@/types/game',
      );
      expect(report).not.toHaveBeenCalled();
    });

    it('allows external imports', () => {
      const report = runRule(
        '/Users/dev/RAWeb/resources/js/common/hooks/useCardTooltip.ts',
        'react',
      );
      expect(report).not.toHaveBeenCalled();
    });

    it('allows core importing from core', () => {
      const report = runRule(
        '/Users/dev/RAWeb/resources/js/core/hooks/useAuth.ts',
        '@/core/services/api',
      );
      expect(report).not.toHaveBeenCalled();
    });
  });

  describe('invalid cases', () => {
    it('reports common importing from feature', () => {
      const report = runRule(
        '/Users/dev/RAWeb/resources/js/common/utils/sortAchievements.ts',
        '@/features/forums/components/ForumPostCard/ForumPostCard',
      );
      expect(report).toHaveBeenCalledWith(
        expect.objectContaining({
          messageId: 'crossBoundaryImport',
          data: { source: 'common', target: 'feature' },
        }),
      );
    });

    it('reports common importing from shared', () => {
      const report = runRule(
        '/Users/dev/RAWeb/resources/js/common/components/ManageButton/ManageButton.tsx',
        '@/shared/components/Header',
      );
      expect(report).toHaveBeenCalledWith(
        expect.objectContaining({
          messageId: 'crossBoundaryImport',
          data: { source: 'common', target: 'shared' },
        }),
      );
    });

    it('reports shared importing from feature', () => {
      const report = runRule(
        '/Users/dev/RAWeb/resources/js/shared/components/Navigation.tsx',
        '@/features/games/hooks/useGameBacklogState',
      );
      expect(report).toHaveBeenCalledWith(
        expect.objectContaining({
          messageId: 'crossBoundaryImport',
          data: { source: 'shared', target: 'feature' },
        }),
      );
    });

    it('reports feature importing from another feature', () => {
      const report = runRule(
        '/Users/dev/RAWeb/resources/js/features/games/components/GameHeaderSlotContent/GameHeaderSlotContent.tsx',
        '@/features/users/components/UserProfile',
      );
      expect(report).toHaveBeenCalledWith(
        expect.objectContaining({
          messageId: 'crossFeatureImport',
          data: { from: 'feature/games', to: 'feature/users' },
        }),
      );
    });

    it('reports application code importing from tools', () => {
      const report = runRule(
        '/Users/dev/RAWeb/resources/js/common/utils/sortAchievements.ts',
        '@/tools/crowdin-download',
      );
      expect(report).toHaveBeenCalledWith(
        expect.objectContaining({
          messageId: 'toolsImport',
        }),
      );
    });

    it('reports core importing from common', () => {
      const report = runRule(
        '/Users/dev/RAWeb/resources/js/core/hooks/useAuth.ts',
        '@/common/utils/sortAchievements',
      );
      expect(report).toHaveBeenCalledWith(
        expect.objectContaining({
          messageId: 'crossBoundaryImport',
          data: { source: 'core', target: 'common' },
        }),
      );
    });

    it('reports core importing from shared', () => {
      const report = runRule(
        '/Users/dev/RAWeb/resources/js/core/hooks/useAuth.ts',
        '@/shared/components/Header',
      );
      expect(report).toHaveBeenCalledWith(
        expect.objectContaining({
          messageId: 'crossBoundaryImport',
          data: { source: 'core', target: 'shared' },
        }),
      );
    });

    it('reports core importing from feature', () => {
      const report = runRule(
        '/Users/dev/RAWeb/resources/js/core/services/api.ts',
        '@/features/forums/components/ForumPostCard/ForumPostCard',
      );
      expect(report).toHaveBeenCalledWith(
        expect.objectContaining({
          messageId: 'crossBoundaryImport',
          data: { source: 'core', target: 'feature' },
        }),
      );
    });
  });
});
