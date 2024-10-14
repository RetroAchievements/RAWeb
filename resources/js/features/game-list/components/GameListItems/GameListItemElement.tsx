import { useLaravelReactI18n } from 'laravel-react-i18n';
import { type FC, useState } from 'react';
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
import { usePageProps } from '@/common/hooks/usePageProps';
import { useWantToPlayGamesList } from '@/common/hooks/useWantToPlayGamesList';
import { cn } from '@/utils/cn';

import { ChipOfInterest } from './ChipOfInterest';
import { GameListItemDrawerBacklogToggleButton } from './GameListItemDrawerBacklogToggleButton';
import { GameListItemDrawerContent } from './GameListItemDrawerContent';

/**
 * 🔴 If you make layout updates to this component, you must
 *    also update <LoadingGameListItem />'s layout. It's
 *    important that the loading skeleton always matches
 *    the real component's layout.
 */

interface GameListItemElementProps {
  gameListEntry: App.Platform.Data.GameListEntry;

  /** If it's the last item, don't show a border at the bottom. */
  isLastItem?: boolean;

  sortFieldId?: string;
}

export const GameListItemElement: FC<GameListItemElementProps> = ({
  gameListEntry,
  sortFieldId,
  isLastItem = false,
}) => {
  const { game, playerGame, isInBacklog } = gameListEntry;

  const { auth } = usePageProps();

  const { t } = useLaravelReactI18n();

  const { addToWantToPlayGamesList, isPending, removeFromWantToPlayGamesList } =
    useWantToPlayGamesList();

  /**
   * Invalidation of infinite queries is _very_ expensive, both for the
   * user (needing to refetch all loaded pages in the infinite scroll), and
   * for us (needing to actually query/load all that data quickly).
   * To avoid this, we'll use optimistic state.
   */
  const [isInBacklogOptimistic, setIsInBacklogOptimistic] = useState(isInBacklog ?? false);

  const handleToggleFromBacklogClick = () => {
    if (!auth?.user && typeof window !== 'undefined') {
      window.location.href = route('login');

      return;
    }

    setIsInBacklogOptimistic((prev) => !prev);

    const mutationPromise = isInBacklogOptimistic
      ? removeFromWantToPlayGamesList(game.id, game.title, {
          t_successMessage: t('Removed :gameTitle from playlist!', { gameTitle: game.title }),
        })
      : addToWantToPlayGamesList(game.id, game.title, {
          t_successMessage: t('Added :gameTitle to playlist!', { gameTitle: game.title }),
        });

    mutationPromise.catch(() => {
      setIsInBacklogOptimistic(isInBacklog ?? false);
    });
  };

  return (
    <BaseDrawer shouldScaleBackground={false}>
      <div className="flex gap-3">
        <GameAvatar {...game} size={48} hasTooltip={false} showLabel={false} />

        <div className="flex-grow truncate">
          <div className="flex flex-col gap-1">
            <a href={route('game.show', { game: game.id })} className="truncate tracking-tight">
              <GameTitle title={game.title} />
            </a>

            <div className="flex items-center gap-1">
              {game.system ? (
                <SystemChip {...game.system} className="light:bg-neutral-200/70" />
              ) : null}

              {sortFieldId ? (
                <ChipOfInterest
                  game={game}
                  playerGame={playerGame ?? undefined}
                  sortFieldId={sortFieldId}
                />
              ) : null}
            </div>
          </div>
        </div>

        <div className="-mr-2.5 flex self-center">
          <button
            className="p-2 text-neutral-100 light:text-neutral-950"
            onClick={handleToggleFromBacklogClick}
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
            <button className="p-2 text-neutral-100 light:text-neutral-950">
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
              onToggle={(newValue) => setIsInBacklogOptimistic(newValue)}
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