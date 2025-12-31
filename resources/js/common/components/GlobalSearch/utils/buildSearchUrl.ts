import type { SearchMode } from '@/common/models';

/**
 * Builds a URL for the search page with optional query and scope parameters.
 * We use this instead of Ziggy's route() helper because that doesn't work in React islands.
 */
export const buildSearchUrl = (query: string, scope: SearchMode): string => {
  const params = new URLSearchParams();

  if (query) {
    params.set('query', query);
  }

  if (scope !== 'all') {
    params.set('scope', scope);
  }

  const queryString = params.toString();

  return queryString ? `/search?${queryString}` : '/search';
};
