import { keepPreviousData, useQuery } from '@tanstack/react-query';
import axios from 'axios';
import { useState } from 'react';
import { route } from 'ziggy-js';

/**
 * TODO in the future, support multiple kinds of searches.
 * right now, this just searches for users.
 *
 * @see SearchApiController.php
 */

export interface SearchQueryResponse {
  users: App.Data.User[];
}

export function useSearchQuery(options: { initialSearchTerm: string } = { initialSearchTerm: '' }) {
  const [searchTerm, setSearchTerm] = useState(options.initialSearchTerm);

  return {
    searchTerm,
    setSearchTerm,
    ...useQuery<App.Data.User[]>({
      queryKey: ['search', searchTerm],

      queryFn: async () => {
        const response = await axios.get<SearchQueryResponse>(
          route('api.search.index', { q: searchTerm }),
        );

        return response.data.users;
      },

      enabled: searchTerm.length >= 3,
      placeholderData: keepPreviousData,
      refetchInterval: false,
    }),
  };
}
