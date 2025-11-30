import type { ColumnSort } from '@tanstack/react-table';
import { type FC, useState } from 'react';
import type { RouteName } from 'ziggy-js';

import { BaseDialog } from '@/common/components/+vendor/BaseDialog';
import { useGameBacklogState } from '@/common/hooks/useGameBacklogState';

import { GameListItemContent } from './GameListItemContent';
import { GameListItemDialogContent } from './GameListItemDialogContent';

interface GameListItemElementProps {
  apiRouteName: RouteName;
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
  apiRouteName,
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

  const [isDialogOpen, setIsDialogOpen] = useState(false);

  if (shouldHideItemIfNotInBacklog && !backlogState.isInBacklogMaybeOptimistic) {
    return null;
  }

  const handleToggleBacklogFromDialogButton = () => {
    const isViewingWantToPlayGames = shouldHideItemIfNotInBacklog;

    if (isViewingWantToPlayGames) {
      setIsDialogOpen(false);
    }

    setTimeout(() => {
      backlogState.toggleBacklog({ shouldHideToasts: !isViewingWantToPlayGames });
    }, 200);
  };

  return (
    <BaseDialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
      <GameListItemContent
        apiRouteName={apiRouteName}
        backlogState={backlogState}
        defaultChipOfInterest={defaultChipOfInterest}
        defaultColumnSort={defaultColumnSort}
        gameListEntry={gameListEntry}
        isLastItem={isLastItem}
        sortFieldId={sortFieldId}
      />

      <GameListItemDialogContent
        backlogState={backlogState}
        gameListEntry={gameListEntry}
        onToggleBacklog={handleToggleBacklogFromDialogButton}
      />
    </BaseDialog>
  );
};
