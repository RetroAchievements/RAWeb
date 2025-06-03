import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { FaGamepad } from 'react-icons/fa';
import { ImTrophy } from 'react-icons/im';
import type { IconType } from 'react-icons/lib';
import { LuNetwork, LuUsers } from 'react-icons/lu';

import { BaseCommandGroup, BaseCommandItem } from '@/common/components/+vendor/BaseCommand';
import type { useSearchQuery } from '@/common/hooks/queries/useSearchQuery';

import type { SearchMode } from '../../models/search-mode.model';
import { AchievementResultDisplay } from './AchievementResultDisplay';
import { GameResultDisplay } from './GameResultDisplay';
import { HubResultDisplay } from './HubResultDisplay';
import { UserResultDisplay } from './UserResultDisplay';

// TODO switch all window.location.assign to use router.visit() and strongly-typed routes
// - this is not currently possible because Inertia/Ziggy are unavailable in Blade contexts

type SearchResult =
  | App.Data.User
  | App.Platform.Data.Game
  | App.Platform.Data.Achievement
  | App.Platform.Data.GameSet;

interface SearchSection {
  key: string;
  heading: string;
  results: SearchResult[];
  relevance: number;
  limit: number;
  icon: IconType;
  render: (item: SearchResult) => React.ReactNode;
}

interface SearchResultsProps {
  currentSearchMode: SearchMode;
  searchResults: ReturnType<typeof useSearchQuery>['data'];
  onClose: () => void;
}

export const SearchResults: FC<SearchResultsProps> = ({
  currentSearchMode,
  searchResults,
  onClose,
}) => {
  const { t } = useTranslation();

  if (!searchResults) {
    return null;
  }

  const sections: SearchSection[] = [
    {
      key: 'users',
      heading: t('Users'),
      results: searchResults.results.users || [],
      relevance: searchResults.scopeRelevance.users || 0,
      limit: 3,
      icon: LuUsers,

      render: (user) => {
        const safeUser = user as App.Data.User;

        return (
          <BaseCommandItem
            key={`user-${safeUser.displayName}`}
            onSelect={() => {
              window.location.assign(`/user/${safeUser.displayName}`);
              onClose();
            }}
          >
            <UserResultDisplay user={safeUser} />
          </BaseCommandItem>
        );
      },
    },
    {
      key: 'games',
      heading: t('Games'),
      results: searchResults.results.games || [],
      relevance: searchResults.scopeRelevance.games || 0,
      limit: 6,
      icon: FaGamepad,

      render: (game) => (
        <BaseCommandItem
          key={`game-${game.id}`}
          className="group"
          onSelect={() => {
            window.location.assign(`/game/${game.id}`);
            onClose();
          }}
        >
          <GameResultDisplay game={game as App.Platform.Data.Game} />
        </BaseCommandItem>
      ),
    },
    {
      key: 'hubs',
      heading: t('Hubs'),
      results: searchResults.results.hubs || [],
      relevance: searchResults.scopeRelevance.hubs || 0,
      limit: 4,
      icon: LuNetwork,

      render: (hub) => (
        <BaseCommandItem
          key={`hub-${hub.id}`}
          onSelect={() => {
            window.location.assign(`/hub/${hub.id}`);
            onClose();
          }}
        >
          <HubResultDisplay hub={hub as App.Platform.Data.GameSet} />
        </BaseCommandItem>
      ),
    },
    {
      key: 'achievements',
      heading: t('Achievements'),
      results: searchResults.results.achievements || [],
      relevance: searchResults.scopeRelevance.achievements || 0,
      limit: 3,
      icon: ImTrophy,

      render: (achievement) => (
        <BaseCommandItem
          key={`achievement-${achievement.id}`}
          onSelect={() => {
            window.location.assign(`/achievement/${achievement.id}`);
            onClose();
          }}
        >
          <AchievementResultDisplay achievement={achievement as App.Platform.Data.Achievement} />
        </BaseCommandItem>
      ),
    },
  ];

  const sectionsWithResults = sections.filter((section) => section.results.length > 0);

  // Use smart section ordering with fallback to logical defaults.
  sectionsWithResults.sort((a, b) => {
    // If one section has significantly higher relevance (>0.3 difference), prioritize it.
    const relevanceDiff = b.relevance - a.relevance;
    if (Math.abs(relevanceDiff) > 0.3) {
      return relevanceDiff > 0 ? 1 : -1;
    }

    // Otherwise, use logical default ordering.
    const defaultOrder: Record<string, number> = {
      games: 1,
      hubs: 2,
      users: 3,
      achievements: 4,
    };

    return (defaultOrder[a.key] as number) - (defaultOrder[b.key] as number);
  });

  const maxResultsSize = 10;

  return (
    <>
      {sectionsWithResults.map((section) => {
        const results = section.results.slice(
          0,
          currentSearchMode === 'all' ? section.limit : maxResultsSize,
        );

        return (
          <BaseCommandGroup
            key={section.key}
            data-testid="search-results"
            heading={
              <div className="flex items-center justify-between">
                <span className="flex items-center gap-1.5 light:text-neutral-800">
                  <section.icon className="size-4" />
                  {section.heading}
                </span>
                <span className="text-muted-foreground text-xs light:text-neutral-800">
                  {t('{{val, number}} results', { val: results.length, count: results.length })}
                </span>
              </div>
            }
          >
            {results.map(section.render)}
          </BaseCommandGroup>
        );
      })}
    </>
  );
};
