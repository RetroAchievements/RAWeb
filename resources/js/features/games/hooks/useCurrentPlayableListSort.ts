import { router } from '@inertiajs/react';
import { useAtom } from 'jotai';

import { usePageProps } from '@/common/hooks/usePageProps';
import type { PlayableListSortOrder } from '@/common/models';

import { currentPlayableListSortAtom } from '../state/games.atoms';

export function useCurrentPlayableListSort() {
  const { defaultSort } = usePageProps<App.Platform.Data.GameShowPageProps>();

  const [currentPlayableListSort, internal_setCurrentPlayableListSort] = useAtom(
    currentPlayableListSortAtom,
  );

  const setCurrentPlayableListSort = (sort: PlayableListSortOrder) => {
    internal_setCurrentPlayableListSort(sort);

    const url = new URL(window.location.href);

    // Remove the sort parameter if it matches the default sort order.
    if (sort === defaultSort) {
      url.searchParams.delete('sort');
    } else {
      url.searchParams.set('sort', sort);
    }

    router.replace({
      url: url.toString(),
      preserveScroll: true,
      preserveState: true,
    });
  };

  return { currentPlayableListSort, setCurrentPlayableListSort };
}
