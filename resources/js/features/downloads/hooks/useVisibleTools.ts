import { useAtomValue } from 'jotai';

import { usePageProps } from '@/common/hooks/usePageProps';

import { searchQueryAtom, selectedPlatformIdAtom } from '../state/downloads.atoms';

export function useVisibleTools() {
  const { allTools } = usePageProps<App.Http.Data.DownloadsPageProps>();

  const selectedPlatformId = useAtomValue(selectedPlatformIdAtom);
  const searchQuery = useAtomValue(searchQueryAtom);

  // Filter tools based on criteria.
  const filteredTools = allTools.filter((emulator) => {
    // Include if platform is undefined or tool supports the selected platform.
    const matchesPlatform =
      typeof selectedPlatformId !== 'number' ||
      emulator.platforms?.some((platform) => platform.id === selectedPlatformId);

    // Only apply the search filter if the query is at least 3 characters.
    const trimmedQuery = searchQuery?.trim() || '';
    const matchesSearch =
      trimmedQuery.length < 3 ||
      emulator.name?.toLowerCase().includes(trimmedQuery.toLowerCase()) ||
      emulator.originalName?.toLowerCase().includes(trimmedQuery.toLowerCase());

    return emulator.hasOfficialSupport && matchesPlatform && matchesSearch;
  });

  // Sort the filtered Tools.
  const visibleTools = [...filteredTools].sort((a, b) => {
    // Sort alphabetically by name.
    return a.name.localeCompare(b.name);
  });

  return { visibleTools };
}
