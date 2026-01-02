import type { FC } from 'react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';

import { useSearchQuery } from '@/common/hooks/queries/useSearchQuery';
import { usePageProps } from '@/common/hooks/usePageProps';
import type { SearchMode } from '@/common/models';

import { useSearchUrlSync } from '../../hooks/useSearchUrlSync';
import { allScopes } from '../../utils/allScopes';
import { scopeToApiScopes } from '../../utils/scopeToApiScopes';
import { ScopeSelector } from '../ScopeSelector';
import { SearchInput } from '../SearchInput';
import { SearchPagination } from '../SearchPagination';
import { SearchResultsContainer } from '../SearchResultsContainer';

export const SearchMainRoot: FC = () => {
  const { initialQuery, initialScope, initialPage } = usePageProps<App.Http.Data.SearchPageProps>();
  const { t } = useTranslation();

  const [query, setQuery] = useState(initialQuery);
  const [scope, setScope] = useState<SearchMode>(
    (allScopes.includes(initialScope as SearchMode) ? initialScope : 'all') as SearchMode,
  );
  const [page, setPage] = useState(initialPage);

  // Only use pagination for single-scope searches (not 'all').
  const shouldUsePagination = scope !== 'all';

  const currentApiScopes = scopeToApiScopes[scope];

  const {
    data: searchResults,
    isLoading,
    setSearchTerm,
  } = useSearchQuery({
    initialSearchTerm: initialQuery,
    scopes: currentApiScopes,
    page: shouldUsePagination ? page : undefined,
    perPage: shouldUsePagination ? 50 : undefined,
  });

  // Sync the current search to URL query params.
  useSearchUrlSync({ query, scope, page });

  const handleSearch = (newQuery: string) => {
    setQuery(newQuery);
    setSearchTerm(newQuery);
    setPage(1);
  };

  const handleScopeChange = (newScope: SearchMode) => {
    setScope(newScope);
    setPage(1);
  };

  const handlePageChange = (newPage: number) => {
    setPage(newPage);
  };

  return (
    <div className="flex flex-col gap-6">
      <h1 className="text-h3 font-semibold">{t('Search')}</h1>

      <SearchInput query={query} isLoading={isLoading} onSearch={handleSearch} />

      <ScopeSelector scope={scope} onScopeChange={handleScopeChange} />

      <div className="flex min-h-[50vh] flex-col gap-4">
        <SearchResultsContainer searchResults={searchResults} isLoading={isLoading} query={query} />

        {searchResults?.pagination && shouldUsePagination ? (
          <SearchPagination
            currentPage={searchResults.pagination.currentPage}
            lastPage={searchResults.pagination.lastPage}
            onPageChange={handlePageChange}
          />
        ) : null}
      </div>
    </div>
  );
};
