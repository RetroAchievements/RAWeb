import { Command as CommandPrimitive } from 'cmdk';
import { AnimatePresence, motion } from 'motion/react';
import { type FC, type KeyboardEvent, useEffect, useState } from 'react';
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
import { BaseSeparator } from '@/common/components/+vendor/BaseSeparator';
import { useSearchQuery } from '@/common/hooks/queries/useSearchQuery';
import type { SearchMode } from '@/common/models';
import { cn } from '@/common/utils/cn';

import { BootState } from './components/BootState';
import { HelperFooter } from './components/HelperFooter';
import { SearchModeSelector } from './components/SearchModeSelector';
import { SearchResults } from './components/SearchResults';
import { SearchResultsSkeleton } from './components/SearchResultsSkeleton';
import { useGlobalSearchDebounce } from './hooks/useGlobalSearchDebounce';
import { useScrollToTopOnSearchResults } from './hooks/useScrollToTopOnSearchResults';
import { buildSearchUrl } from './utils/buildSearchUrl';

interface GlobalSearchProps {
  isOpen: boolean;
  onOpenChange: (open: boolean) => void;
}

export const GlobalSearch: FC<GlobalSearchProps> = ({ isOpen, onOpenChange }) => {
  const { t } = useTranslation();

  const [searchMode, setSearchMode] = useState<SearchMode>('all');
  const [rawQuery, setRawQuery] = useState('');

  const {
    isLoading,
    setSearchTerm,
    setShouldUsePlaceholderData,
    data: searchResults,
  } = useSearchQuery({
    // This is required for global search to work in Blade contexts.
    route: '/internal-api/search',
    scopes:
      searchMode === 'all' ? ['users', 'games', 'hubs', 'events', 'achievements'] : [searchMode],
  });

  useGlobalSearchDebounce({ rawQuery, setSearchTerm });
  const scrollContainerRef = useScrollToTopOnSearchResults({ searchResults, isLoading });

  /**
   * Handles keyboard events for the Command component.
   *
   * The cmdk library has built-in keyboard navigation that intercepts the Space key
   * to scroll selected items into view. This causes an unwanted scroll jump when
   * users are typing in the search input and press Space. This handler prevents
   * that default behavior and manually inserts the space character instead.
   */
  const handleCommandKeyDown = (event: KeyboardEvent<HTMLDivElement>) => {
    // Prevent Space from causing scroll jumps when input is focused.
    if (event.key === ' ' && document.activeElement?.tagName === 'INPUT') {
      event.preventDefault();

      // Manually insert a space at the cursor position.
      const input = document.activeElement as HTMLInputElement;
      const start = input.selectionStart as number;
      const end = input.selectionEnd as number;
      const value = input.value;
      const newValue = value.substring(0, start) + ' ' + value.substring(end);

      setRawQuery(newValue);

      // Restore the cursor position at the end of the event queue.
      setTimeout(() => {
        input.selectionStart = input.selectionEnd = start + 1;
      });
    }
  };

  useEffect(() => {
    if (!isLoading) {
      // Turn `placeholderData` in the query back on. With it enabled,
      // users won't see a flash of empty state in between search queries.
      setShouldUsePlaceholderData(true);
    }
  }, [isLoading, setShouldUsePlaceholderData]);

  // Reset search state whenever the dialog closes.
  useEffect(() => {
    if (!isOpen) {
      setRawQuery('');
      setSearchTerm('');

      // Without this, reopening the dialog on the same page and making a
      // new search would show a flash of previous results.
      setShouldUsePlaceholderData(false);
    }
  }, [isOpen, setSearchTerm, setShouldUsePlaceholderData]);

  const areNoResultsFound =
    !isLoading &&
    searchResults &&
    !searchResults.results?.users?.length &&
    !searchResults.results?.games?.length &&
    !searchResults.results?.hubs?.length &&
    !searchResults.results?.events?.length &&
    !searchResults.results?.achievements?.length;

  return (
    <BaseDialog open={isOpen} onOpenChange={onOpenChange}>
      {/* These are just to prevent a11y issues. */}
      <BaseDialogTitle className="sr-only">{t('Search')}</BaseDialogTitle>
      <BaseDialogDescription className="sr-only">{t('Search')}</BaseDialogDescription>

      <BaseDialogContent className="h-full max-w-2xl overflow-hidden border-0 p-0 sm:h-[calc(min(80vh,600px))] sm:border">
        <BaseCommand
          shouldFilter={false}
          className={cn([
            '**:[[cmdk-input-wrapper]]:border-none',
            '**:[[cmdk-group-heading]]:px-2 **:[[cmdk-group-heading]]:font-medium',
            '[&_[cmdk-group]:not([hidden])_~[cmdk-group]]:pt-0 **:[[cmdk-group]]:px-2',
            '[&_[cmdk-input-wrapper]_svg]:h-5 [&_[cmdk-input-wrapper]_svg]:w-5',
            '**:[[cmdk-input]]:h-12 **:[[cmdk-input]]:text-base **:[[cmdk-input]]:sm:text-sm',
            "[&_[cmdk-item][data-selected='true']]:bg-neutral-800/50 [&_[cmdk-item][data-selected='true']]:light:bg-neutral-200/50",
            '**:[[cmdk-item]]:cursor-pointer **:[[cmdk-item]]:px-2',
            '**:[[cmdk-item]]:py-3 [&_[cmdk-item]_svg]:h-5 [&_[cmdk-item]_svg]:w-5',
          ])}
          onKeyDown={handleCommandKeyDown}
        >
          <div className="mb-3 pt-2 pl-2 sm:hidden">
            <div className="relative flex items-center">
              <CommandPrimitive.Input
                value={rawQuery}
                onValueChange={setRawQuery}
                className="peer w-[calc(100%-48px)] border-text ps-10 focus:outline-hidden"
                placeholder={t('Search')}
              />

              <div className="pointer-events-none absolute inset-y-0 inset-s-0 flex items-center justify-center ps-3 peer-disabled:opacity-50">
                <LuSearch className="size-5" />
              </div>
            </div>
          </div>

          <div className="hidden sm:block">
            <BaseCommandInput
              placeholder={t('Search')}
              value={rawQuery}
              onValueChange={setRawQuery}
            />
          </div>

          <div className="flex flex-wrap items-center gap-2 px-3 pb-3 sm:justify-between">
            <SearchModeSelector
              onChange={(newMode) => setSearchMode(newMode)}
              selectedMode={searchMode}
              rawQuery={rawQuery}
            />

            {/* Because this is a React island, we can't use InertiaLink. */}
            <a href={buildSearchUrl(rawQuery, searchMode)} className="hidden items-center sm:flex">
              {t('Browse')} <LuChevronRight className="size-4" />
            </a>
          </div>

          <BaseSeparator className="light:bg-neutral-200" />

          <BaseCommandList ref={scrollContainerRef} className="max-h-none grow overflow-y-auto">
            <AnimatePresence mode="wait">
              {/* Loading spinner in the top right. */}
              {isLoading ? (
                <motion.div
                  key="loading-spinner"
                  initial={{ opacity: 0 }}
                  animate={{ opacity: 1 }}
                  exit={{ opacity: 0 }}
                  transition={{ duration: 0.15 }}
                  className="absolute top-4 right-10 z-10"
                >
                  <LuLoaderCircle className="size-4 animate-spin" />
                </motion.div>
              ) : null}

              {/* Show the initial boot state only when we've never searched before. */}
              {!searchResults && rawQuery.length < 3 ? (
                <motion.div
                  key="initial-state"
                  initial={{ opacity: 0, transform: 'translateY(10px)' }}
                  animate={{ opacity: 1, transform: 'translateY(0px)' }}
                  exit={{ opacity: 0, transform: 'translateY(-10px)' }}
                  transition={{ delay: 0.15, duration: 0.2 }}
                  className="px-3 py-30 text-center"
                >
                  <BootState />
                </motion.div>
              ) : null}

              {/* Show loading skeletons when actually searching. */}
              {isLoading && rawQuery.length >= 3 && !searchResults ? (
                <motion.div
                  key="loading-skeleton"
                  initial={{ opacity: 0 }}
                  animate={{ opacity: 1 }}
                  exit={{ opacity: 0 }}
                  transition={{ duration: 0.2 }}
                >
                  <SearchResultsSkeleton />
                </motion.div>
              ) : null}

              {/* Show no results only when the search is complete and no results are found. */}
              {areNoResultsFound ? (
                <motion.div
                  key="no-results"
                  initial={{ opacity: 0, transform: 'translateY(10px)' }}
                  animate={{ opacity: 1, transform: 'translateY(0px)' }}
                  exit={{ opacity: 0, transform: 'translateY(-10px)' }}
                  transition={{ duration: 0.2 }}
                  className="py-37.5"
                >
                  <div className="flex items-center justify-center text-sm text-neutral-500">
                    {t('No results found.')}
                  </div>
                </motion.div>
              ) : null}

              {/* Always show SearchResults when we have data. */}
              {searchResults && !areNoResultsFound ? (
                <motion.div
                  key="search-results"
                  initial={{ opacity: 0 }}
                  animate={{ opacity: 1 }}
                  exit={{ opacity: 0 }}
                  transition={{ duration: 0.2 }}
                >
                  <SearchResults
                    currentSearchMode={searchMode}
                    searchResults={searchResults}
                    onClose={() => onOpenChange(false)}
                  />
                </motion.div>
              ) : null}
            </AnimatePresence>
          </BaseCommandList>

          <HelperFooter />
        </BaseCommand>
      </BaseDialogContent>
    </BaseDialog>
  );
};
