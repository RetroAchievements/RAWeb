import { useAtomValue } from 'jotai';
import { useMemo } from 'react';

import { usePageProps } from '@/common/hooks/usePageProps';

import {
  searchQueryAtom,
  selectedPlatformIdAtom,
  selectedSystemIdAtom,
  sortByAtom,
} from '../state/downloads.atoms';

export function useVisibleEmulators() {
  const { allEmulators, popularEmulatorsBySystem } =
    usePageProps<App.Http.Data.DownloadsPageProps>();

  const selectedPlatformId = useAtomValue(selectedPlatformIdAtom);
  const selectedSystemId = useAtomValue(selectedSystemIdAtom);
  const searchQuery = useAtomValue(searchQueryAtom);
  const sortBy = useAtomValue(sortByAtom);

  const visibleEmulators = useMemo(() => {
    // Filter emulators based on criteria.
    const filteredEmulators = allEmulators.filter((emulator) => {
      // Include if platform is undefined or emulator supports the selected platform.
      const matchesPlatform =
        typeof selectedPlatformId !== 'number' ||
        emulator.platforms?.some((platform) => platform.id === selectedPlatformId);

      // Include if system is undefined or emulator supports the selected system.
      const matchesSystem =
        typeof selectedSystemId !== 'number' ||
        selectedSystemId === 0 ||
        emulator.systems?.some((system) => system.id === selectedSystemId);

      // Only apply the search filter if the query is at least 3 characters.
      const trimmedQuery = searchQuery?.trim() || '';
      const matchesSearch =
        trimmedQuery.length < 3 ||
        emulator.name?.toLowerCase().includes(trimmedQuery.toLowerCase()) ||
        emulator.originalName?.toLowerCase().includes(trimmedQuery.toLowerCase());

      return emulator.hasOfficialSupport && matchesPlatform && matchesSystem && matchesSearch;
    });

    // Sort the filtered emulators.
    return [...filteredEmulators].sort((a, b) => {
      if (sortBy === 'alphabetical') {
        // Sort alphabetically by name.
        return a.name.localeCompare(b.name);
      } else if (sortBy === 'popularity') {
        // Get the appropriate popularity list based on whether a system is selected.
        // Use system ID 0 (overall popularity) when no system is selected.
        const systemIdForPopularity = selectedSystemId || 0;
        const popularityList = popularEmulatorsBySystem[systemIdForPopularity] || [];

        // Find the position of each emulator in the popularity list.
        const aIndex = popularityList.indexOf(a.id);
        const bIndex = popularityList.indexOf(b.id);

        // Handle cases where emulators aren't in the popularity list.
        if (aIndex === -1 && bIndex === -1) {
          // If neither is in the list, fall back to alphabetical.
          return a.name.localeCompare(b.name);
        } else if (aIndex === -1) {
          // If only a is not in the list, b comes first.
          return 1;
        } else if (bIndex === -1) {
          // If only b is not in the list, a comes first.
          return -1;
        }

        // Both are in the list, sort by their positions.
        return aIndex - bIndex;
      }

      // Default fallback to alphabetical sorting.
      return a.name.localeCompare(b.name);
    });
  }, [
    allEmulators,
    popularEmulatorsBySystem,
    selectedPlatformId,
    selectedSystemId,
    searchQuery,
    sortBy,
  ]);

  return { visibleEmulators };
}
