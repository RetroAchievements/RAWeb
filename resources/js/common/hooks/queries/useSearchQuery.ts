import { keepPreviousData, useQuery } from '@tanstack/react-query';
import axios from 'axios';
import { useState } from 'react';
import { route } from 'ziggy-js';

type SearchScope = 'users' | 'games' | 'hubs' | 'achievements';

interface SearchQueryResponse {
  results: {
    users?: App.Data.User[];
    games?: App.Platform.Data.Game[];
    hubs?: App.Platform.Data.GameSet[];
    achievements?: App.Platform.Data.Achievement[];
  };
  query: string;
  scopes: SearchScope[];
  scopeRelevance: {
    users?: number;
    games?: number;
    hubs?: number;
    achievements?: number;
  };
}

interface UseSearchQueryOptions {
  initialSearchTerm?: string;

  /** Blade contexts don't have access to Ziggy routes. */
  route?: string;

  scopes?: SearchScope[];
}

export function useSearchQuery(options: UseSearchQueryOptions = {}) {
  const { initialSearchTerm = '', scopes = ['users'], route: customRoute } = options;
  const [searchTerm, setSearchTerm] = useState(initialSearchTerm);

  return {
    searchTerm,
    setSearchTerm,
    ...useQuery<SearchQueryResponse>({
      queryKey: ['search', searchTerm, scopes, customRoute],

      queryFn: async () => {
        const params = new URLSearchParams({
          q: searchTerm,
        });

        if (scopes.length > 0) {
          params.append('scope', scopes.join(','));
        }

        const url = customRoute || route('api.search.index');
        const response = await axios.get<SearchQueryResponse>(url + '?' + params.toString());

        return response.data;
      },

      enabled: searchTerm.length >= 3,
      placeholderData: searchTerm.length >= 3 ? keepPreviousData : undefined,
      refetchInterval: false,
    }),
  };
}
