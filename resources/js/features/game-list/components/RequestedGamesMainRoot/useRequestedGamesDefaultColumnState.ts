import type { ColumnFiltersState, ColumnSort } from '@tanstack/react-table';

import type { DefaultColumnState } from '../../models';

interface UseRequestedGamesDefaultColumnStateProps {
  targetUser?: App.Data.User | null;
}

export function useRequestedGamesDefaultColumnState({
  targetUser,
}: UseRequestedGamesDefaultColumnStateProps): DefaultColumnState {
  const defaultColumnFilters: ColumnFiltersState = [
    { id: 'system', value: targetUser ? ['all'] : ['supported'] },
    { id: 'achievementsPublished', value: ['none'] },
    { id: 'hasActiveOrInReviewClaims', value: ['any'] },
  ];

  if (targetUser) {
    defaultColumnFilters.push({ id: 'user', value: [targetUser.displayName] });
  }

  // When filtering by user, sort by title instead of numRequests.
  const defaultColumnSort: ColumnSort = targetUser
    ? { id: 'title', desc: false }
    : { id: 'numRequests', desc: true };

  const defaultColumnVisibility: Partial<Record<App.Platform.Enums.GameListSortField, boolean>> = {
    hasActiveOrInReviewClaims: true,
  };

  return { defaultColumnFilters, defaultColumnSort, defaultColumnVisibility };
}
