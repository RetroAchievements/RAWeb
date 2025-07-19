/* eslint-disable @typescript-eslint/no-explicit-any -- we don't even care about the results type */

import { useEffect, useRef } from 'react';

interface UseScrollToTopOnSearchResultsProps {
  searchResults: any;
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
  const previousIdsRef = useRef<string>('');

  useEffect(() => {
    const resultsKey = searchResults ? JSON.stringify(searchResults) : '';

    if (!isLoading && searchResults && resultsKey !== previousIdsRef.current) {
      scrollContainerRef.current?.scrollTo({ top: 0 });
      previousIdsRef.current = resultsKey;
    }
  }, [searchResults, isLoading]);

  return scrollContainerRef;
}
