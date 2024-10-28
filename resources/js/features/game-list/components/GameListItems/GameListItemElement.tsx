import { useLaravelReactI18n } from 'laravel-react-i18n';
import { type FC } from 'react';
import { MdClose } from 'react-icons/md';
import { RxDotsVertical } from 'react-icons/rx';

import { baseButtonVariants } from '@/common/components/+vendor/BaseButton';
import {
  BaseDrawer,
  BaseDrawerContent,
  BaseDrawerFooter,
  BaseDrawerHeader,
  BaseDrawerTitle,
  BaseDrawerTrigger,
} from '@/common/components/+vendor/BaseDrawer';
import { GameAvatar } from '@/common/components/GameAvatar';
import { GameTitle } from '@/common/components/GameTitle';
import { SystemChip } from '@/common/components/SystemChip';
import { cn } from '@/utils/cn';

import { ChipOfInterest } from './ChipOfInterest';
import { GameListItemDrawerBacklogToggleButton } from './GameListItemDrawerBacklogToggleButton';
import { GameListItemDrawerContent } from './GameListItemDrawerContent';
import { useGameBacklogState } from './useGameBacklogState';

/**
 * 🔴 If you make layout updates to this component, you must
 *    also update <LoadingGameListItem />'s layout. It's
 *    important that the loading skeleton always matches
 *    the real component's layout.
 */

interface GameListItemElementProps {
  gameListEntry: App.Platform.Data.GameListEntry;

  /**
   * If truthy, non-backlog items will be optimistically hidden from
   * the list. This is useful specifically for the user's Want to
   * Play Games List page, where we don't want to trigger a refetch,
   * but we do want to hide items when they're removed.
   */
  shouldHideItemIfNotInBacklog?: boolean;

  /** If it's the last item, don't show a border at the bottom. */
  isLastItem?: boolean;

  /** TODO strongly-type this */
  sortFieldId?: string;
}

export const GameListItemElement: FC<GameListItemElementProps> = ({
  gameListEntry,
  sortFieldId,
  shouldHideItemIfNotInBacklog = false,
  isLastItem = false,
}) => {
  const { game, playerGame, isInBacklog } = gameListEntry;

  const { t } = useLaravelReactI18n();

  const {
    isPending,
    toggleBacklog,
    isInBacklogMaybeOptimistic: isInBacklogOptimistic,
  } = useGameBacklogState({
    game,
    isInitiallyInBacklog: isInBacklog ?? false,
  });

  if (shouldHideItemIfNotInBacklog && !isInBacklogOptimistic) {
    return null;
  }

  return (
    <BaseDrawer shouldScaleBackground={false} modal={false}>
      <div className="flex gap-3">
        <GameAvatar {...game} size={48} hasTooltip={false} showLabel={false} />

        <div className="flex-grow truncate">
          <div className="flex flex-col gap-1">
            <a href={route('game.show', { game: game.id })} className="truncate tracking-tight">
              <GameTitle title={game.title} />
            </a>

            <div className="flex flex-wrap items-center gap-1">
              {game.system ? (
                <SystemChip
                  {...game.system}
                  className="light:bg-neutral-200/70"
                  // Hide the system label when it's a date field. Otherwise, we gets really cramped,
                  // especially when the localized date format is long, eg: "20 de abril de 1998"
                  showLabel={sortFieldId !== 'releasedAt' && sortFieldId !== 'lastUpdated'}
                />
              ) : null}

              {playerGame ? (
                <ChipOfInterest game={game} playerGame={playerGame} fieldId="progress" />
              ) : null}

              {sortFieldId && sortFieldId !== 'progress' ? (
                <ChipOfInterest
                  game={game}
                  playerGame={playerGame ?? undefined}
                  fieldId={sortFieldId}
                />
              ) : null}
            </div>
          </div>
        </div>

        <div className="-mr-1 flex self-center">
          <button
            className="p-3 text-neutral-100 light:text-neutral-950"
            onClick={toggleBacklog}
            disabled={isPending}
          >
            <MdClose
              className={cn(
                'h-4 w-4 transition-transform',
                'disabled:!text-neutral-100 light:disabled:text-neutral-950',
                isInBacklogOptimistic ? '' : 'rotate-45',
              )}
            />

            <span className="sr-only">
              {isInBacklogOptimistic
                ? t('Remove from Want To Play Games')
                : t('Add to Want to Play Games')}
            </span>
          </button>

          <BaseDrawerTrigger asChild>
            <button className="p-3 text-neutral-100 light:text-neutral-950">
              <RxDotsVertical className="h-4 w-4" />
              <span className="sr-only">{t('Open game details')}</span>
            </button>
          </BaseDrawerTrigger>
        </div>
      </div>

      {isLastItem ? null : (
        <hr className="ml-14 mt-2 border-neutral-700 light:border-neutral-200" />
      )}

      <BaseDrawerContent>
        <div className="mx-auto w-full max-w-sm overflow-hidden">
          <BaseDrawerHeader>
            <BaseDrawerTitle>{t('Game Details')}</BaseDrawerTitle>
          </BaseDrawerHeader>

          <GameListItemDrawerContent {...gameListEntry} />
        </div>

        <BaseDrawerFooter>
          <div className="grid grid-cols-2 gap-3">
            <GameListItemDrawerBacklogToggleButton
              game={game}
              isInBacklog={isInBacklogOptimistic}
            />

            {/* TODO after migrating the game page to Inertia, prefetch this link */}
            <a
              href={route('game.show', { game: gameListEntry.game.id })}
              className={baseButtonVariants({ variant: 'secondary' })}
            >
              {t('Open Game')}
            </a>
          </div>
        </BaseDrawerFooter>
      </BaseDrawerContent>
    </BaseDrawer>
  );
};
