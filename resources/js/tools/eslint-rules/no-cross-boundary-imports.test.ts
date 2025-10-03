import { RuleTester } from '@typescript-eslint/rule-tester';
import * as vitest from 'vitest';

import { rule, RULE_NAME } from './no-cross-boundary-imports.js';

RuleTester.afterAll = vitest.afterAll;
RuleTester.it = vitest.it;
RuleTester.itOnly = vitest.it.only;
RuleTester.describe = vitest.describe;

const ruleTester = new RuleTester();

ruleTester.run(RULE_NAME, rule, {
  valid: [
    // Common can import from common.
    {
      filename: '/Users/dev/RAWeb/resources/js/common/utils/sortAchievements.ts',
      code: `import { getIsInteractiveElement } from "@/common/utils/getIsInteractiveElement";`,
    },
    // Common can import from common via relative path.
    {
      filename: '/Users/dev/RAWeb/resources/js/common/utils/sortAchievements.ts',
      code: `import { getIsInteractiveElement } from "./getIsInteractiveElement";`,
    },
    // Shared can import from common.
    {
      filename: '/Users/dev/RAWeb/resources/js/shared/components/Layout.tsx',
      code: `import { formatDate } from "@/common/utils/l10n/formatDate";`,
    },
    // Shared can import from shared.
    {
      filename: '/Users/dev/RAWeb/resources/js/shared/components/Layout.tsx',
      code: `import { Header } from "@/shared/components/Header";`,
    },
    // Feature can import from common.
    {
      filename: '/Users/dev/RAWeb/resources/js/features/games/components/GameList.tsx',
      code: `import { sortAchievements } from "@/common/utils/sortAchievements";`,
    },
    // Feature can import from shared.
    {
      filename: '/Users/dev/RAWeb/resources/js/features/games/components/GameList.tsx',
      code: `import { DataTable } from "@/shared/components/DataTable";`,
    },
    // Feature can import from same feature.
    {
      filename:
        '/Users/dev/RAWeb/resources/js/features/forums/components/ForumPostCard/ForumPostCard.tsx',
      code: `import { ForumPostCardMeta } from "@/features/forums/components/ForumPostCard/ForumPostCardMeta";`,
    },
    // Feature can import from same feature via relative path.
    {
      filename:
        '/Users/dev/RAWeb/resources/js/features/forums/components/ForumPostCard/ForumPostCard.tsx',
      code: `import { ForumPostCardMeta } from "./ForumPostCardMeta";`,
    },
    // Feature can import from same feature via relative path (up directory).
    {
      filename:
        '/Users/dev/RAWeb/resources/js/features/forums/components/ForumPostCard/ForumPostCardMeta/ForumPostCardMeta.tsx',
      code: `import { CommentMetaChip } from "../CommentMetaChip";`,
    },
    // Imports from tall-stack are allowed.
    {
      filename: '/Users/dev/RAWeb/resources/js/common/hooks/useCardTooltip.ts',
      code: `import { Button } from "@/tall-stack/components/Button";`,
    },
    // Imports from types are allowed.
    {
      filename: '/Users/dev/RAWeb/resources/js/features/games/components/GameList.tsx',
      code: `import type { Game } from "@/types/game";`,
    },
    // External imports are allowed.
    {
      filename: '/Users/dev/RAWeb/resources/js/common/hooks/useCardTooltip.ts',
      code: `import React from "react";`,
    },
    {
      filename: '/Users/dev/RAWeb/resources/js/features/games/components/GameForm.tsx',
      code: `import { z } from "zod";`,
    },
    // Imports from pages are allowed (pages is not enforced).
    {
      filename: '/Users/dev/RAWeb/resources/js/features/games/components/GameList.tsx',
      code: `import { Layout } from "@/pages/Layout";`,
    },
    // Core can import from core.
    {
      filename: '/Users/dev/RAWeb/resources/js/core/hooks/useAuth.ts',
      code: `import { ApiService } from "@/core/services/api";`,
    },
  ],
  invalid: [
    // Common cannot import from feature.
    {
      filename: '/Users/dev/RAWeb/resources/js/common/utils/sortAchievements.ts',
      code: `import { ForumPostCard } from "@/features/forums/components/ForumPostCard/ForumPostCard";`,
      errors: [
        {
          messageId: 'crossBoundaryImport',
          data: {
            source: 'common',
            target: 'feature',
          },
        },
      ],
    },
    // Common cannot import from shared.
    {
      filename: '/Users/dev/RAWeb/resources/js/common/components/ManageButton/ManageButton.tsx',
      code: `import { Header } from "@/shared/components/Header";`,
      errors: [
        {
          messageId: 'crossBoundaryImport',
          data: {
            source: 'common',
            target: 'shared',
          },
        },
      ],
    },
    // Shared cannot import from feature.
    {
      filename: '/Users/dev/RAWeb/resources/js/shared/components/Navigation.tsx',
      code: `import { useGameBacklogState } from "@/features/games/hooks/useGameBacklogState";`,
      errors: [
        {
          messageId: 'crossBoundaryImport',
          data: {
            source: 'shared',
            target: 'feature',
          },
        },
      ],
    },
    // Feature cannot import from another feature.
    {
      filename:
        '/Users/dev/RAWeb/resources/js/features/games/components/GameHeaderSlotContent/GameHeaderSlotContent.tsx',
      code: `import { UserProfile } from "@/features/users/components/UserProfile";`,
      errors: [
        {
          messageId: 'crossFeatureImport',
          data: {
            from: 'feature/games',
            to: 'feature/users',
          },
        },
      ],
    },
    // Feature cannot import from another feature.
    {
      filename: '/Users/dev/RAWeb/resources/js/features/achievements/hooks/useAchievement.ts',
      code: `import { GameModel } from "@/features/games/models/game";`,
      errors: [
        {
          messageId: 'crossFeatureImport',
          data: {
            from: 'feature/achievements',
            to: 'feature/games',
          },
        },
      ],
    },
    // Feature cannot import from another feature via relative path.
    {
      filename: '/Users/dev/RAWeb/resources/js/features/games/utils/gameFormSchemas.ts',
      code: `import { mapAchievements } from "../../achievements/utils/mapAchievements";`,
      errors: [
        {
          messageId: 'crossFeatureImport',
          data: {
            from: 'feature/games',
            to: 'feature/achievements',
          },
        },
      ],
    },
    // Common cannot import from feature via relative path.
    {
      filename: '/Users/dev/RAWeb/resources/js/common/hooks/useCardTooltip.ts',
      code: `import { GameForm } from "../../features/games/GameForm";`,
      errors: [
        {
          messageId: 'crossBoundaryImport',
          data: {
            source: 'common',
            target: 'feature',
          },
        },
      ],
    },
    // Shared cannot import from feature via relative path.
    {
      filename: '/Users/dev/RAWeb/resources/js/shared/components/Layout.tsx',
      code: `import { GameModel } from "../../features/games/models/game";`,
      errors: [
        {
          messageId: 'crossBoundaryImport',
          data: {
            source: 'shared',
            target: 'feature',
          },
        },
      ],
    },
    // Nothing can import from tools.
    {
      filename: '/Users/dev/RAWeb/resources/js/common/utils/sortAchievements.ts',
      code: `import { crowdinDownload } from "@/tools/crowdin-download";`,
      errors: [
        {
          messageId: 'toolsImport',
        },
      ],
    },
    // Features cannot import from tools.
    {
      filename:
        '/Users/dev/RAWeb/resources/js/features/games/components/GameHeaderSlotContent/GameHeaderSlotContent.tsx',
      code: `import { crowdinUpload } from "@/tools/crowdin-upload";`,
      errors: [
        {
          messageId: 'toolsImport',
        },
      ],
    },
    // Core cannot import from common.
    {
      filename: '/Users/dev/RAWeb/resources/js/core/hooks/useAuth.ts',
      code: `import { sortAchievements } from "@/common/utils/sortAchievements";`,
      errors: [
        {
          messageId: 'crossBoundaryImport',
          data: {
            source: 'core',
            target: 'common',
          },
        },
      ],
    },
    // Core cannot import from shared.
    {
      filename: '/Users/dev/RAWeb/resources/js/core/hooks/useAuth.ts',
      code: `import { Header } from "@/shared/components/Header";`,
      errors: [
        {
          messageId: 'crossBoundaryImport',
          data: {
            source: 'core',
            target: 'shared',
          },
        },
      ],
    },
    // Core cannot import from feature.
    {
      filename: '/Users/dev/RAWeb/resources/js/core/services/api.ts',
      code: `import { ForumPostCard } from "@/features/forums/components/ForumPostCard/ForumPostCard";`,
      errors: [
        {
          messageId: 'crossBoundaryImport',
          data: {
            source: 'core',
            target: 'feature',
          },
        },
      ],
    },
  ],
});
