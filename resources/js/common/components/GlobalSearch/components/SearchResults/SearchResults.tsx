import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { FaGamepad } from 'react-icons/fa';
import { ImTrophy } from 'react-icons/im';
import type { IconType } from 'react-icons/lib';
import { LuCalendar, LuNetwork, LuUsers } from 'react-icons/lu';

import { BaseCommandGroup, BaseCommandItem } from '@/common/components/+vendor/BaseCommand';
import type { useSearchQuery } from '@/common/hooks/queries/useSearchQuery';

import type { SearchMode } from '../../models/search-mode.model';
import { AchievementResultDisplay } from './AchievementResultDisplay';
import { EventResultDisplay } from './EventResultDisplay';
import { GameResultDisplay } from './GameResultDisplay';
import { HubResultDisplay } from './HubResultDisplay';
import { UserResultDisplay } from './UserResultDisplay';

// TODO switch all window.location.assign to use router.visit() and strongly-typed routes
// - this is not currently possible because Inertia/Ziggy are unavailable in Blade contexts

type SearchResult =
  | App.Data.User
  | App.Platform.Data.Game
  | App.Platform.Data.Achievement
  | App.Platform.Data.GameSet
  | App.Platform.Data.Event;

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
          <BaseCommandItem key={`user-${safeUser.displayName}`} asChild={true} onSelect={onClose}>
            <a href={`/user/${safeUser.displayName}`}>
              <UserResultDisplay user={safeUser} />
            </a>
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
          asChild={true}
          className="group"
          onSelect={onClose}
        >
          <a href={`/game/${game.id}`}>
            <GameResultDisplay game={game as App.Platform.Data.Game} />
          </a>
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
        <BaseCommandItem key={`hub-${hub.id}`} asChild={true} onSelect={onClose}>
          <a href={`/hub/${hub.id}`}>
            <HubResultDisplay hub={hub as App.Platform.Data.GameSet} />
          </a>
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
        <BaseCommandItem key={`achievement-${achievement.id}`} asChild={true} onSelect={onClose}>
          <a href={`/achievement/${achievement.id}`}>
            <AchievementResultDisplay achievement={achievement as App.Platform.Data.Achievement} />
          </a>
        </BaseCommandItem>
      ),
    },

    {
      key: 'events',
      heading: t('Events'),
      results: searchResults.results.events || [],
      relevance: searchResults.scopeRelevance.events || 0,
      limit: 4,
      icon: LuCalendar,

      render: (event) => (
        <BaseCommandItem key={`event-${event.id}`} asChild={true} onSelect={onClose}>
          <a href={`/event/${event.id}`}>
            <EventResultDisplay event={event as App.Platform.Data.Event} />
          </a>
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
      events: 4,
      achievements: 5,
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
