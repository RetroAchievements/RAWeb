import { router } from '@inertiajs/react';
import { useAtom, useSetAtom } from 'jotai';

import { usePageProps } from '@/common/hooks/usePageProps';

import { currentListViewAtom, currentPlayableListSortAtom } from '../state/games.atoms';

export function useCurrentListView() {
  const { allLeaderboards } = usePageProps<App.Platform.Data.GameShowPageProps>();

  const [currentListView, internal_setCurrentListView] = useAtom(currentListViewAtom);
  const setCurrentPlayableListSort = useSetAtom(currentPlayableListSortAtom);

  const setCurrentListView = (view?: 'achievements' | 'leaderboards') => {
    if (!view) {
      return;
    }

    internal_setCurrentListView(view);

    // Set the appropriate default sort when switching views.
    if (view === 'leaderboards') {
      setCurrentPlayableListSort('displayOrder');
    } else {
      setCurrentPlayableListSort('normal');
    }

    const url = new URL(window.location.href);

    if (view === 'leaderboards') {
      url.searchParams.set('view', 'leaderboards');
    } else {
      url.searchParams.delete('view');
    }

    window.history.replaceState({}, '', url.toString());

    /**
     * FALLBACK: If we're switching to leaderboards and that deferred data
     * isn't loaded yet, fetch it. This handles edge cases where preloading
     * didn't work or the user clicked very quickly.
     */
    if (view === 'leaderboards' && allLeaderboards === undefined) {
      router.reload({ only: ['allLeaderboards'] });
    }
  };

  return { currentListView, setCurrentListView };
}
