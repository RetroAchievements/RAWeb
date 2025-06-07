import { useEffect, useRef } from 'react';

interface UseScrollToTopOnSearchResultsProps {
  searchResults: unknown;
  isLoading: boolean;
}

/**
 * Scrolls a container element to the top when new search results arrive.
 * Returns a ref that should be attached to the scrollable container.
 */
export function useScrollToTopOnSearchResults({
  searchResults,
  isLoading,
}: UseScrollToTopOnSearchResultsProps) {
  const scrollContainerRef = useRef<HTMLDivElement>(null);
  const previousResultsRef = useRef(searchResults);

  useEffect(() => {
    // Only scroll to top when we have new results and we're not loading.
    if (!isLoading && searchResults && searchResults !== previousResultsRef.current) {
      scrollContainerRef.current?.scrollTo({ top: 0 });
      previousResultsRef.current = searchResults;
    }
  }, [searchResults, isLoading]);

  return scrollContainerRef;
}
