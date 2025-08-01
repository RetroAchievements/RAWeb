import type { ColumnSort } from '@tanstack/react-table';
import { type FC, useState } from 'react';

import { BaseDrawer } from '@/common/components/+vendor/BaseDrawer';

import { useGameBacklogState } from '../useGameBacklogState';
import { GameListItemContent } from './GameListItemContent';
import { GameListItemDrawerContent } from './GameListItemDrawerContent';

interface GameListItemElementProps {
  gameListEntry: App.Platform.Data.GameListEntry;

  defaultChipOfInterest?: App.Platform.Enums.GameListSortField;
  defaultColumnSort?: ColumnSort;

  /**
   * If truthy, non-backlog items will be optimistically hidden from
   * the list. This is useful specifically for the user's Want to
   * Play Games List page, where we don't want to trigger a refetch,
   * but we do want to hide items when they're removed.
   */
  shouldHideItemIfNotInBacklog?: boolean;

  /** If it's the last item, don't show a border at the bottom. */
  isLastItem?: boolean;

  sortFieldId?: App.Platform.Enums.GameListSortField;
}

export const GameListItemElement: FC<GameListItemElementProps> = ({
  defaultChipOfInterest,
  defaultColumnSort,
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

  const [isDrawerOpen, setIsDrawerOpen] = useState(false);

  if (shouldHideItemIfNotInBacklog && !backlogState.isInBacklogMaybeOptimistic) {
    return null;
  }

  const handleToggleBacklogFromDrawerButton = () => {
    const isViewingWantToPlayGames = shouldHideItemIfNotInBacklog;

    if (isViewingWantToPlayGames) {
      setIsDrawerOpen(false);
    }

    setTimeout(() => {
      backlogState.toggleBacklog({ shouldHideToasts: !isViewingWantToPlayGames });
    }, 200);
  };

  return (
    <BaseDrawer open={isDrawerOpen} onOpenChange={setIsDrawerOpen} shouldScaleBackground={false}>
      <GameListItemContent
        backlogState={backlogState}
        defaultChipOfInterest={defaultChipOfInterest}
        defaultColumnSort={defaultColumnSort}
        gameListEntry={gameListEntry}
        isLastItem={isLastItem}
        sortFieldId={sortFieldId}
      />

      <GameListItemDrawerContent
        backlogState={backlogState}
        gameListEntry={gameListEntry}
        onToggleBacklog={handleToggleBacklogFromDrawerButton}
      />
    </BaseDrawer>
  );
};
