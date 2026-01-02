import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { FaGamepad } from 'react-icons/fa';
import { ImTrophy } from 'react-icons/im';
import { LuCalendar, LuMessageSquare, LuNetwork, LuUsers } from 'react-icons/lu';
import { route } from 'ziggy-js';

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

  return (
    <div
      className={cn(
        '-mx-2.5 flex flex-col gap-6 border-y border-neutral-700 bg-neutral-950/80 p-2',
        'sm:mx-0 sm:rounded-lg sm:border-x',
        'light:border-neutral-200 light:bg-white',
      )}
    >
      {/* Users */}
      {searchResults.results.users?.length ? (
        <ResultSection title={t('Users')} icon={<LuUsers className="size-4" />}>
          {searchResults.results.users.map((user) => (
            <ResultItem
              key={`user-${user.displayName}`}
              href={route('user.show', { user: user.displayName })}
              isInertiaLink={false}
            >
              <UserResultDisplay user={user} />
            </ResultItem>
          ))}
        </ResultSection>
      ) : null}

      {/* Games */}
      {searchResults.results.games?.length ? (
        <ResultSection title={t('Games')} icon={<FaGamepad className="size-4" />}>
          {searchResults.results.games.map((game) => (
            <ResultItem
              key={`game-${game.id}`}
              href={route('game.show', { game: game.id })}
              isInertiaLink={true}
            >
              <GameResultDisplay game={game} />
            </ResultItem>
          ))}
        </ResultSection>
      ) : null}

      {/* Hubs */}
      {searchResults.results.hubs?.length ? (
        <ResultSection title={t('Hubs')} icon={<LuNetwork className="size-4" />}>
          {searchResults.results.hubs.map((hub) => (
            <ResultItem
              key={`hub-${hub.id}`}
              href={route('hub.show', { gameSet: hub.id })}
              isInertiaLink={true}
            >
              <HubResultDisplay hub={hub} />
            </ResultItem>
          ))}
        </ResultSection>
      ) : null}

      {/* Achievements */}
      {searchResults.results.achievements?.length ? (
        <ResultSection title={t('Achievements')} icon={<ImTrophy className="size-4" />}>
          {searchResults.results.achievements.map((achievement) => (
            <ResultItem
              key={`achievement-${achievement.id}`}
              href={route('achievement.show', { achievementId: achievement.id })}
              isInertiaLink={false}
            >
              <AchievementResultDisplay achievement={achievement} />
            </ResultItem>
          ))}
        </ResultSection>
      ) : null}

      {/* Events */}
      {searchResults.results.events?.length ? (
        <ResultSection title={t('Events')} icon={<LuCalendar className="size-4" />}>
          {searchResults.results.events.map((event) => (
            <ResultItem
              key={`event-${event.id}`}
              href={route('event.show', { event: event.id })}
              isInertiaLink={true}
            >
              <EventResultDisplay event={event} />
            </ResultItem>
          ))}
        </ResultSection>
      ) : null}

      {/* Forum Comments */}
      {searchResults.results.forum_comments?.length ? (
        <ResultSection title={t('Forum Posts')} icon={<LuMessageSquare className="size-4" />}>
          {searchResults.results.forum_comments.map((comment) => (
            <ResultItem
              key={`forum-comment-${comment.id}`}
              href={
                route('forum-topic.show', {
                  topic: comment.forumTopicId!,
                  _query: { comment: comment.id },
                }) + `#${comment.id}`
              }
              isInertiaLink={true}
            >
              <ForumCommentResultDisplay forumComment={comment} />
            </ResultItem>
          ))}
        </ResultSection>
      ) : null}

      {/* Comments */}
      {searchResults.results.comments?.length ? (
        <ResultSection title={t('Comments')} icon={<LuMessageSquare className="size-4" />}>
          {searchResults.results.comments.map((comment) => (
            <ResultItem
              key={`comment-${comment.id}`}
              href={comment.url ?? '#'}
              isInertiaLink={false}
            >
              <CommentResultDisplay comment={comment} />
            </ResultItem>
          ))}
        </ResultSection>
      ) : null}
    </div>
  );
};
