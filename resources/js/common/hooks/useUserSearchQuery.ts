import { keepPreviousData, useQuery, type UseQueryResult } from '@tanstack/react-query';
import axios from 'axios';
import { type Dispatch, type SetStateAction, useState } from 'react';
import { route } from 'ziggy-js';

interface SearchQueryResponse {
  results: {
    users?: App.Data.User[];
  };
  query: string;
  scopes: string[];
  scopeRelevance: {
    users?: number;
  };
}

interface UseUserSearchQueryOptions {
  initialSearchTerm?: string;
}

export function useUserSearchQuery(options: UseUserSearchQueryOptions = {}): UseQueryResult<
  App.Data.User[]
> & {
  searchTerm: string;
  setSearchTerm: Dispatch<SetStateAction<string>>;
} {
  const { initialSearchTerm = '' } = options;
  const [searchTerm, setSearchTerm] = useState(initialSearchTerm);

  const query = useQuery<App.Data.User[]>({
    queryKey: ['search', searchTerm, ['users']],

    queryFn: async () => {
      const params = new URLSearchParams({
        q: searchTerm,
        scope: 'users',
      });

      const response = await axios.get<SearchQueryResponse>(
        route('api.search.index') + '?' + params.toString(),
      );

      return response.data.results.users || [];
    },

    enabled: searchTerm.length >= 3,
    placeholderData: keepPreviousData,
    refetchInterval: false,
  });

  return {
    ...query,
    searchTerm,
    setSearchTerm,
  };
}
