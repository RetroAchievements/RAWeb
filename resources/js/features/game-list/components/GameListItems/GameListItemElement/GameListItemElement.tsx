import { type FC } from 'react';

import { BaseDrawer } from '@/common/components/+vendor/BaseDrawer';

import { useGameBacklogState } from '../useGameBacklogState';
import { GameListItemContent } from './GameListItemContent';
import { GameListItemDrawerContent } from './GameListItemDrawerContent';

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
  const { game, isInBacklog } = gameListEntry;

  const backlogState = useGameBacklogState({
    game,
    isInitiallyInBacklog: isInBacklog ?? false,
  });

  if (shouldHideItemIfNotInBacklog && !backlogState.isInBacklogMaybeOptimistic) {
    return null;
  }

  return (
    <BaseDrawer shouldScaleBackground={false} modal={false}>
      <GameListItemContent
        backlogState={backlogState}
        gameListEntry={gameListEntry}
        isLastItem={isLastItem}
        sortFieldId={sortFieldId}
      />

      <GameListItemDrawerContent backlogState={backlogState} gameListEntry={gameListEntry} />
    </BaseDrawer>
  );
};
