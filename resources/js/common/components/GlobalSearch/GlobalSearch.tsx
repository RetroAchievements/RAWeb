import { type FC, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { LuChevronRight, LuLoaderCircle, LuSearch } from 'react-icons/lu';

import {
  BaseCommand,
  BaseCommandInput,
  BaseCommandList,
} from '@/common/components/+vendor/BaseCommand';
import {
  BaseDialog,
  BaseDialogContent,
  BaseDialogDescription,
  BaseDialogTitle,
} from '@/common/components/+vendor/BaseDialog';
import { useSearchQuery } from '@/common/hooks/queries/useSearchQuery';
import { cn } from '@/common/utils/cn';

import { BaseSeparator } from '../+vendor/BaseSeparator';
import { HelperFooter } from './components/HelperFooter';
import { SearchModeSelector } from './components/SearchModeSelector';
import { SearchResults } from './components/SearchResults';
import { useGlobalSearchDebounce } from './hooks/useGlobalSearchDebounce';
import { useGlobalSearchHotkey } from './hooks/useGlobalSearchHotkey';
import { useScrollToTopOnSearchResults } from './hooks/useScrollToTopOnSearchResults';
import type { SearchMode } from './models';

interface GlobalSearchProps {
  isOpen: boolean;
  onOpenChange: (open: boolean) => void;
}

export const GlobalSearch: FC<GlobalSearchProps> = ({ isOpen, onOpenChange }) => {
  const { t } = useTranslation();

  const [searchMode, setSearchMode] = useState<SearchMode>('all');
  const [rawQuery, setRawQuery] = useState('');

  const {
    setSearchTerm,
    isLoading,
    data: searchResults,
  } = useSearchQuery({
    // This is required for global search to work in Blade contexts.
    route: '/internal-api/search',
    scopes:
      searchMode === 'all' ? ['users', 'games', 'hubs', 'events', 'achievements'] : [searchMode],
  });

  useGlobalSearchDebounce({ rawQuery, setSearchTerm });
  useGlobalSearchHotkey({ onOpenChange });
  const scrollContainerRef = useScrollToTopOnSearchResults({ searchResults, isLoading });

  const handleOnOpenChange = (open: boolean) => {
    onOpenChange(open);

    if (!open) {
      setRawQuery('');
      setSearchTerm('');
    }
  };

  const areNoResultsFound =
    !isLoading &&
    searchResults &&
    !searchResults.results?.users?.length &&
    !searchResults.results?.games?.length &&
    !searchResults.results?.hubs?.length &&
    !searchResults.results?.events?.length &&
    !searchResults.results?.achievements?.length;

  return (
    <BaseDialog open={isOpen} onOpenChange={handleOnOpenChange}>
      {/* These are just to prevent a11y issues. */}
      <BaseDialogTitle className="sr-only">{t('Search')}</BaseDialogTitle>
      <BaseDialogDescription className="sr-only">{t('Search')}</BaseDialogDescription>

      <BaseDialogContent className="h-full max-w-2xl overflow-hidden border-0 p-0 sm:h-[calc(min(80vh,600px))] sm:border">
        <BaseCommand
          shouldFilter={false}
          className={cn([
            '[&_[cmdk-input-wrapper]]:border-none',
            '[&_[cmdk-group-heading]]:px-2 [&_[cmdk-group-heading]]:font-medium',
            '[&_[cmdk-group]:not([hidden])_~[cmdk-group]]:pt-0 [&_[cmdk-group]]:px-2',
            '[&_[cmdk-input-wrapper]_svg]:h-5 [&_[cmdk-input-wrapper]_svg]:w-5 [&_[cmdk-input]]:h-12',
            "[&_[cmdk-item][data-selected='true']]:bg-neutral-800/50 [&_[cmdk-item][data-selected='true']]:light:bg-neutral-200/50",
            '[&_[cmdk-item]]:cursor-pointer [&_[cmdk-item]]:px-2',
            '[&_[cmdk-item]]:py-3 [&_[cmdk-item]_svg]:h-5 [&_[cmdk-item]_svg]:w-5',
          ])}
        >
          <BaseCommandInput
            placeholder={t('Search')}
            value={rawQuery}
            onValueChange={setRawQuery}
          />

          <div className="flex flex-wrap items-center gap-2 px-3 pb-3 sm:justify-between">
            <SearchModeSelector
              selectedMode={searchMode}
              onChange={(newMode) => setSearchMode(newMode)}
            />

            <a
              href={rawQuery ? `/searchresults.php?s=${rawQuery}` : '/searchresults.php'}
              className="flex items-center"
            >
              {t('Browse')} <LuChevronRight className="size-4" />
            </a>
          </div>

          <BaseSeparator className="light:bg-neutral-200" />

          <BaseCommandList
            ref={scrollContainerRef}
            className="max-h-none flex-grow overflow-y-auto"
          >
            {isLoading ? (
              <div className="absolute right-10 top-4 z-10">
                <LuLoaderCircle className="size-4 animate-spin" />
              </div>
            ) : null}

            {/* Show initial state only when we've never searched before. */}
            {!searchResults && rawQuery.length < 3 ? (
              <div className="px-3 py-[120px] text-center">
                <div className="flex flex-col items-center justify-center gap-3 text-sm">
                  <LuSearch className="size-12 opacity-50" />

                  <p className="text-balance">
                    {t('Search for games, hubs, users, and achievements')}
                  </p>
                  <p className="text-xs">{t('Type at least 3 characters to begin')}</p>
                </div>
              </div>
            ) : null}

            {/* Show no results only when search is complete and no results are actually found. */}
            {areNoResultsFound ? (
              <div className="py-[150px]">
                <div className="flex items-center justify-center text-sm text-neutral-500">
                  {t('No results found.')}
                </div>
              </div>
            ) : null}

            {/* Always show SearchResults when we have data. */}
            {searchResults ? (
              <SearchResults
                currentSearchMode={searchMode}
                searchResults={searchResults}
                onClose={() => onOpenChange(false)}
              />
            ) : null}
          </BaseCommandList>

          <HelperFooter />
        </BaseCommand>
      </BaseDialogContent>
    </BaseDialog>
  );
};
