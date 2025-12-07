import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuLoaderCircle, LuSearch } from 'react-icons/lu';

import { cn } from '@/common/utils/cn';

interface SearchInputProps {
  isLoading: boolean;
  onSearch: (query: string) => void;
  query: string;
}

export const SearchInput: FC<SearchInputProps> = ({ isLoading, onSearch, query }) => {
  const { t } = useTranslation();

  return (
    <div className="relative">
      <input
        type="text"
        value={query}
        onChange={(event) => onSearch(event.target.value)}
        placeholder={t('Search...')}
        className={cn(
          'sm:hidden',
          'w-full rounded-lg border border-neutral-700 bg-embed px-4 py-3 pl-11 text-base',
          'focus:border-neutral-500 focus:outline-none light:border-neutral-300 light:focus:border-neutral-400',
        )}
      />

      <input
        type="text"
        value={query}
        onChange={(event) => onSearch(event.target.value)}
        placeholder={t('Search for games, users, achievements, and more...')}
        className={cn(
          'hidden sm:block',
          'w-full rounded-lg border border-neutral-700 bg-embed px-4 py-3 pl-11 text-base',
          'focus:border-neutral-500 focus:outline-none light:border-neutral-300 light:focus:border-neutral-400',
        )}
      />

      <div className="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
        <LuSearch className="size-5 text-neutral-400" />
      </div>

      {isLoading ? (
        <div
          data-testid="search-loading-spinner"
          className="absolute inset-y-0 right-0 flex items-center pr-4"
        >
          <LuLoaderCircle className="size-5 animate-spin text-neutral-400" />
        </div>
      ) : null}
    </div>
  );
};
