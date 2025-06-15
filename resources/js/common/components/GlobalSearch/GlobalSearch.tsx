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
import { cn } from '@/common/utils/cn';

import { BootState } from './components/BootState';
import { HelperFooter } from './components/HelperFooter';
import { SearchModeSelector } from './components/SearchModeSelector';
import { SearchResults } from './components/SearchResults';
import { SearchResultsSkeleton } from './components/SearchResultsSkeleton';
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
  useGlobalSearchHotkey({ onOpenChange });
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

  const handleOnOpenChange = (open: boolean) => {
    onOpenChange(open);

    if (!open) {
      setRawQuery('');
      setSearchTerm('');

      // If we don't do this, if the user reopens the dialog on the same
      // page and makes a new search, they'll see a flash of previous results.
      setShouldUsePlaceholderData(false);
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
            '[&_[cmdk-input-wrapper]_svg]:h-5 [&_[cmdk-input-wrapper]_svg]:w-5',
            '[&_[cmdk-input]]:h-12 [&_[cmdk-input]]:text-[16px] [&_[cmdk-input]]:sm:text-sm',
            "[&_[cmdk-item][data-selected='true']]:bg-neutral-800/50 [&_[cmdk-item][data-selected='true']]:light:bg-neutral-200/50",
            '[&_[cmdk-item]]:cursor-pointer [&_[cmdk-item]]:px-2',
            '[&_[cmdk-item]]:py-3 [&_[cmdk-item]_svg]:h-5 [&_[cmdk-item]_svg]:w-5',
          ])}
          onKeyDown={handleCommandKeyDown}
        >
          <div className="mb-3 pl-2 pt-2 sm:hidden">
            <div className="relative flex items-center">
              <CommandPrimitive.Input
                value={rawQuery}
                onValueChange={setRawQuery}
                className="peer w-[calc(100%-48px)] border-text ps-10 focus:outline-none"
                placeholder={t('Search')}
              />

              <div className="pointer-events-none absolute inset-y-0 start-0 flex items-center justify-center ps-3 peer-disabled:opacity-50">
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

            <a
              href={rawQuery ? `/searchresults.php?s=${rawQuery}` : '/searchresults.php'}
              className="hidden items-center sm:flex"
            >
              {t('Browse')} <LuChevronRight className="size-4" />
            </a>
          </div>

          <BaseSeparator className="light:bg-neutral-200" />

          <BaseCommandList
            ref={scrollContainerRef}
            className="max-h-none flex-grow overflow-y-auto"
          >
            <AnimatePresence mode="wait">
              {/* Loading spinner in the top right. */}
              {isLoading ? (
                <motion.div
                  key="loading-spinner"
                  initial={{ opacity: 0 }}
                  animate={{ opacity: 1 }}
                  exit={{ opacity: 0 }}
                  transition={{ duration: 0.15 }}
                  className="absolute right-10 top-4 z-10"
                >
                  <LuLoaderCircle className="size-4 animate-spin" />
                </motion.div>
              ) : null}

              {/* Show the initial boot state only when we've never searched before. */}
              {!searchResults && rawQuery.length < 3 ? (
                <motion.div
                  key="initial-state"
                  initial={{ opacity: 0, y: 10 }}
                  animate={{ opacity: 1, y: 0 }}
                  exit={{ opacity: 0, y: -10 }}
                  transition={{ delay: 0.15, duration: 0.2 }}
                  className="px-3 py-[120px] text-center"
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
                  initial={{ opacity: 0, y: 10 }}
                  animate={{ opacity: 1, y: 0 }}
                  exit={{ opacity: 0, y: -10 }}
                  transition={{ duration: 0.2 }}
                  className="py-[150px]"
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
