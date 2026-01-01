import { useEffect } from 'react';

import type { SearchMode } from '@/common/models';

interface UseSearchUrlSyncProps {
  page: number;
  query: string;
  scope: SearchMode;
}

/**
 * Syncs search state to the URL without triggering navigation.
 */
export function useSearchUrlSync({ page, query, scope }: UseSearchUrlSyncProps): void {
  useEffect(() => {
    const params = new URLSearchParams();

    if (query) {
      params.set('query', query);
    }
    if (scope !== 'all') {
      params.set('scope', scope);
    }
    if (page > 1) {
      params.set('page', String(page));
    }

    const newUrl = `/search${params.toString() ? '?' + params.toString() : ''}`;
    window.history.replaceState({}, '', newUrl);
  }, [query, scope, page]);
}
