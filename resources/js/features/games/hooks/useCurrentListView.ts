import { useAtom, useSetAtom } from 'jotai';

import { currentListViewAtom, currentPlayableListSortAtom } from '../state/games.atoms';

export function useCurrentListView() {
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
  };

  return { currentListView, setCurrentListView };
}
