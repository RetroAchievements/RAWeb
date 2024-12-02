import { useState } from 'react';

interface UseActivePlayerSearchProps {
  persistedSearchValue?: string;
}

export function useActivePlayerSearch({ persistedSearchValue }: UseActivePlayerSearchProps) {
  const [canShowSearchBar, setCanShowSearchBar] = useState(!!persistedSearchValue);
  const [hasSearched, setHasSearched] = useState(false);
  const [searchValue, setSearchValue] = useState('');

  const handleSearch = (value: string) => {
    setHasSearched(true);
    setSearchValue(value);
  };

  return {
    canShowSearchBar,
    handleSearch,
    hasSearched,
    searchValue,
    setCanShowSearchBar,
  };
}
