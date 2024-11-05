import { useLaravelReactI18n } from 'laravel-react-i18n';
import type { FC } from 'react';
import { MdClose } from 'react-icons/md';
import { RxDotsVertical } from 'react-icons/rx';

import { BaseDrawerTrigger } from '@/common/components/+vendor/BaseDrawer';
import { GameAvatar } from '@/common/components/GameAvatar';
import { GameTitle } from '@/common/components/GameTitle';
import { SystemChip } from '@/common/components/SystemChip';
import { cn } from '@/utils/cn';

import type { useGameBacklogState } from '../../useGameBacklogState';
import { ChipOfInterest } from './ChipOfInterest';

/**
 * 🔴 If you make layout updates to this component, you must
 *    also update <LoadingGameListItem />'s layout. It's
 *    important that the loading skeleton always matches
 *    the real component's layout.
 */

interface GameListItemContentProps {
  backlogState: ReturnType<typeof useGameBacklogState>;
  isLastItem: boolean;
  gameListEntry: App.Platform.Data.GameListEntry;

  sortFieldId?: App.Platform.Enums.GameListSortField;
}

export const GameListItemContent: FC<GameListItemContentProps> = ({
  backlogState,
  gameListEntry,
  isLastItem,
  sortFieldId,
}) => {
  const { t } = useLaravelReactI18n();

  const { game, playerGame } = gameListEntry;

  return (
    <li>
      <div className="flex gap-3">
        <GameAvatar {...game} size={48} hasTooltip={false} showLabel={false} />

        {/* Game info section */}
        {/* TODO if this gets more complex, break it out into another component */}
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
                  // This is undefined because the progress chip is always shown.
                  playerGame={undefined}
                  fieldId={sortFieldId}
                />
              ) : null}
            </div>
          </div>
        </div>

        {/* Action buttons */}
        {/* TODO if this gets more complex, break it out into another component */}
        <div className="-mr-1 flex self-center">
          <button
            className="p-3 text-neutral-100 light:text-neutral-950"
            onClick={() => backlogState.toggleBacklog()}
            disabled={backlogState.isPending}
          >
            <MdClose
              className={cn(
                'h-4 w-4 transition-transform',
                'disabled:!text-neutral-100 light:disabled:text-neutral-950',
                backlogState.isInBacklogMaybeOptimistic ? '' : 'rotate-45',
              )}
            />

            <span className="sr-only">
              {backlogState.isInBacklogMaybeOptimistic
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
    </li>
  );
};
