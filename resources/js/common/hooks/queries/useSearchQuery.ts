import { keepPreviousData, useQuery } from '@tanstack/react-query';
import axios from 'axios';
import { useState } from 'react';
import { route } from 'ziggy-js';

import type { SearchApiScope } from '@/common/models';

interface SearchQueryPagination {
  currentPage: number;
  lastPage: number;
  perPage: number;
  total: number;
}

export interface SearchResults {
  results: {
    users?: App.Data.User[];
    games?: App.Platform.Data.Game[];
    hubs?: App.Platform.Data.GameSet[];
    achievements?: App.Platform.Data.Achievement[];
    events?: App.Platform.Data.Event[];
    forum_comments?: App.Data.ForumTopicComment[];
    comments?: App.Community.Data.Comment[];
  };
  query: string;
  scopes: SearchApiScope[];
  scopeRelevance: {
    users?: number;
    games?: number;
    hubs?: number;
    achievements?: number;
    events?: number;
    forum_comments?: number;
    comments?: number;
  };
  pagination?: SearchQueryPagination;
}

interface UseSearchQueryOptions {
  initialSearchTerm?: string;

  /** Blade contexts don't have access to Ziggy routes. */
  route?: string;

  scopes?: SearchApiScope[];

  /** Optional page for pagination (triggers paginated search when provided). */
  page?: number;

  /** Optional per page limit for pagination. */
  perPage?: number;
}

export function useSearchQuery(options: UseSearchQueryOptions = {}) {
  const { initialSearchTerm = '', scopes = ['users'], route: customRoute, page, perPage } = options;
  const [searchTerm, setSearchTerm] = useState(initialSearchTerm);
  const [shouldUsePlaceholderData, setShouldUsePlaceholderData] = useState(true);

  return {
    searchTerm,
    setSearchTerm,
    setShouldUsePlaceholderData,
    ...useQuery<SearchResults>({
      queryKey: ['search', searchTerm, scopes, customRoute, page, perPage],

      queryFn: async () => {
        const params = new URLSearchParams({
          q: searchTerm,
        });

        if (scopes.length > 0) {
          params.append('scope', scopes.join(','));
        }

        // Add pagination params when a page is provided.
        if (page !== undefined) {
          params.append('page', String(page));
          if (perPage !== undefined) {
            params.append('perPage', String(perPage));
          }
        }

        const url = customRoute || route('api.search.index');
        const response = await axios.get<SearchResults>(url + '?' + params.toString());

        return response.data;
      },

      enabled: searchTerm.length >= 3,
      placeholderData:
        searchTerm.length >= 3 && shouldUsePlaceholderData ? keepPreviousData : undefined,
      refetchInterval: false,
    }),
  };
}
