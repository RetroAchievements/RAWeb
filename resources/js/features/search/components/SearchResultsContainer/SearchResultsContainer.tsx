import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { FaGamepad } from 'react-icons/fa';
import { ImTrophy } from 'react-icons/im';
import { LuCalendar, LuMessageSquare, LuNetwork, LuUsers } from 'react-icons/lu';
import { route } from 'ziggy-js';

import { SearchSection, sortSections } from '@/common/components/GlobalSearch/components/SearchResults';
import { AchievementResultDisplay } from '@/common/components/GlobalSearch/components/SearchResults/AchievementResultDisplay';
import { EventResultDisplay } from '@/common/components/GlobalSearch/components/SearchResults/EventResultDisplay';
import { GameResultDisplay } from '@/common/components/GlobalSearch/components/SearchResults/GameResultDisplay';
import { HubResultDisplay } from '@/common/components/GlobalSearch/components/SearchResults/HubResultDisplay';
import { UserResultDisplay } from '@/common/components/GlobalSearch/components/SearchResults/UserResultDisplay';
import type { SearchResults } from '@/common/hooks/queries/useSearchQuery';
import { cn } from '@/common/utils/cn';

import { CommentResultDisplay } from '../CommentResultDisplay';
import { ForumCommentResultDisplay } from '../ForumCommentResultDisplay';
import { ResultItem } from './ResultItem';
import { ResultSection } from './ResultSection';

interface SearchResultsContainerProps {
  isLoading: boolean;
  query: string;

  searchResults?: SearchResults;
}

export const SearchResultsContainer: FC<SearchResultsContainerProps> = ({
  searchResults,
  isLoading,
  query,
}) => {
  const { t } = useTranslation();

  const areNoResultsFound =
    !isLoading &&
    searchResults &&
    !searchResults.results?.users?.length &&
    !searchResults.results?.games?.length &&
    !searchResults.results?.hubs?.length &&
    !searchResults.results?.events?.length &&
    !searchResults.results?.achievements?.length &&
    !searchResults.results?.forum_comments?.length &&
    !searchResults.results?.comments?.length;

  if (query.length < 3) {
    return (
      <div
        className={cn(
          '-mx-2.5 flex flex-1 items-center justify-center sm:mx-0 sm:w-full sm:rounded-lg',
          'border-y border-neutral-800 bg-neutral-950/80 p-2 text-neutral-400 sm:border-x',
          'light:border-neutral-200 light:bg-white',
        )}
      >
        {t('Enter a search term to get started.')}
      </div>
    );
  }

  if (isLoading) {
    return (
      <div
        className={cn(
          '-mx-2.5 sm:mx-0 sm:w-full',
          'flex flex-col gap-4 border-y border-neutral-800 bg-neutral-950/80 p-2 sm:rounded-lg sm:border-x',
          'light:border-neutral-200 light:bg-white',
        )}
      >
        {[1, 2, 3, 4, 5, 6, 7, 8, 9, 10].map((i) => (
          <div key={i} className="h-16 animate-pulse rounded-lg bg-embed" />
        ))}
      </div>
    );
  }

  if (areNoResultsFound) {
    return (
      <div
        className={cn(
          '-mx-2.5 flex flex-1 items-center justify-center sm:mx-0 sm:w-full sm:rounded-lg',
          'border-y border-neutral-800 bg-neutral-950/80 p-2 text-neutral-400 sm:border-x',
          'light:border-neutral-200 light:bg-white',
        )}
      >
        {t('No results found.')}
      </div>
    );
  }

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
          <ResultItem
            key={`user-${safeUser.displayName}`}
            href={route('user.show', { user: safeUser.displayName })}
            isInertiaLink={false}
          >
            <UserResultDisplay user={safeUser} />
          </ResultItem>
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

      render: (game) => {
        const safeGame = game as App.Platform.Data.Game;

        return (
          <ResultItem
            key={`game-${game.id}`}
            href={route('game.show', { game: safeGame.id })}
            isInertiaLink={true}
          >
            <GameResultDisplay game={safeGame} />
          </ResultItem>
        );
      },
    },

    {
      key: 'hubs',
      heading: t('Hubs'),
      results: searchResults.results.hubs || [],
      relevance: searchResults.scopeRelevance.hubs || 0,
      limit: 4,
      icon: LuNetwork,

      render: (hub) => {
        const safeHub = hub as App.Platform.Data.GameSet

        return (
          <ResultItem
            key={`hub-${safeHub.id}`}
            href={route('hub.show', { gameSet: safeHub.id })}
            isInertiaLink={true}
          >
            <HubResultDisplay hub={safeHub} />
          </ResultItem>
        );
      },
    },

    {
      key: 'achievements',
      heading: t('Achievements'),
      results: searchResults.results.achievements || [],
      relevance: searchResults.scopeRelevance.achievements || 0,
      limit: 3,
      icon: ImTrophy,

      render: (achievement) => {
        const safeAchievement = achievement as App.Platform.Data.Achievement

        return (
          <ResultItem
            key={`achievement-${achievement.id}`}
            href={route('achievement.show', { achievement: safeAchievement.id })}
            isInertiaLink={false}
          >
            <AchievementResultDisplay achievement={safeAchievement} />
          </ResultItem>
        );
      },
    },

    {
      key: 'events',
      heading: t('Events'),
      results: searchResults.results.events || [],
      relevance: searchResults.scopeRelevance.events || 0,
      limit: 4,
      icon: LuCalendar,

      render: (event) => {
        const safeEvent = event as App.Platform.Data.Event

        return (
          <ResultItem
            key={`event-${event.id}`}
            href={route('event.show', { event: safeEvent.id })}
            isInertiaLink={true}
          >
            <EventResultDisplay event={safeEvent} />
          </ResultItem>
        );
      },
    },

    {
      key: 'forum-comments',
      heading: t('Forum Posts'),
      results: searchResults.results.forum_comments || [],
      relevance: searchResults.scopeRelevance.forum_comments || 0,
      limit: 4,
      icon: LuMessageSquare,

      render: (forumComment) => {
        const safeComment = forumComment as App.Data.ForumTopicComment

        return (
          <ResultItem
            key={`forum-comment-${safeComment.id}`}
            href={
              route('forum-topic.show', {
                topic: safeComment.forumTopicId!,
                _query: { comment: safeComment.id },
              }) + `#${safeComment.id}`
            }
            isInertiaLink={true}
          >
            <ForumCommentResultDisplay forumComment={safeComment} />
          </ResultItem>
        )
      }
    },

    {
      key: 'comments',
      heading: t('Comments'),
      results: searchResults.results.comments || [],
      relevance: searchResults.scopeRelevance.comments || 0,
      limit: 4,
      icon: LuMessageSquare,

      render: (comment) => {
        const safeComment = comment as App.Community.Data.Comment

        return (
          <ResultItem
            key={`comment-${safeComment.id}`}
            href={safeComment.url ?? '#'}
            isInertiaLink={false}
          >
            <CommentResultDisplay comment={safeComment} />
          </ResultItem>
        )
      }
    }
  ];

  const sectionsWithResults = sections.filter((section) => section.results.length > 0);

  // Use smart section ordering with fallback to logical defaults.
  sectionsWithResults.sort(sortSections);

  return (
    <div
      className={cn(
        '-mx-2.5 flex flex-col gap-6 border-y border-neutral-700 bg-neutral-950/80 p-2',
        'sm:mx-0 sm:rounded-lg sm:border-x',
        'light:border-neutral-200 light:bg-white',
      )}
    >
      {sectionsWithResults.map((section) => {
        return (
          <ResultSection key={section.key} title={section.heading} icon={<section.icon className="size-4" />}>
            {section.results.map((item) =>
              section.render(item)
            )}
          </ResultSection>
        )
      })}
    </div>
  );
};
