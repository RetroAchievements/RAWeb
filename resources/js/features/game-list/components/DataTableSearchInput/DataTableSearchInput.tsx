import type { Table } from '@tanstack/react-table';
import { useEffect, useRef, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { LuX } from 'react-icons/lu';
import { useDebounce } from 'react-use';

import { BaseInput } from '@/common/components/+vendor/BaseInput';
import {
  BaseTooltip,
  BaseTooltipContent,
  BaseTooltipTrigger,
} from '@/common/components/+vendor/BaseTooltip';
import { cn } from '@/utils/cn';

import { useSearchInputHotkey } from './useSearchInputHotkey';

interface DataTableSearchInputProps<TData> {
  table: Table<TData>;

  /**
   * If truthy, an "X" button can be tapped that wipes the current search value.
   * Intended for mobile users.
   */
  hasClearButton?: boolean;

  /** Disable the "/" hotkey on XS. */
  hasHotkey?: boolean;

  /** The ID of the column that will be used to perform the search. */
  searchColumnId?: string;
}

export function DataTableSearchInput<TData>({
  table,
  hasClearButton = false,
  hasHotkey = true,
  searchColumnId = 'title',
}: DataTableSearchInputProps<TData>) {
  const { t } = useTranslation();

  const initialValue = (table.getColumn(searchColumnId)?.getFilterValue() as string) ?? '';

  const [rawInputValue, setRawInputValue] = useState(initialValue);

  const { hotkeyInputRef } = useSearchInputHotkey({ isEnabled: hasHotkey, key: '/' });

  const isFirstRender = useRef(true);

  /**
   * Listen for changes with column filter state and stay in sync. Otherwise,
   * when the user presses the "Reset" button to reset all filters, our search
   * value will remain. It needs to be reset too.
   */
  useEffect(() => {
    const filterValue = (table.getColumn('title')?.getFilterValue() as string) ?? '';
    setRawInputValue(filterValue);
    // eslint-disable-next-line react-hooks/exhaustive-deps -- this is a valid dependency array
  }, [table.getState().columnFilters]);

  /**
   * Wait until the user is done typing before we fetch from the back-end.
   */
  useDebounce(
    () => {
      // Skip the effect on the first render. Otherwise, column filters
      // be changed simply because the component mounted.
      if (isFirstRender.current) {
        isFirstRender.current = false;

        return;
      }

      table.getColumn('title')?.setFilterValue(rawInputValue);
    },
    getDebounceDuration(rawInputValue),
    [rawInputValue],
  );

  const handleClearClick = () => {
    setRawInputValue('');
    table.getColumn('title')?.setFilterValue('');
  };

  return (
    <div className="w-full sm:w-auto">
      <label htmlFor="search-field" className="sr-only">
        {t('Search games')}
      </label>

      <div className="group relative flex items-center">
        <BaseInput
          id="search-field"
          ref={hotkeyInputRef}
          placeholder={t('Search games...')}
          value={rawInputValue}
          onChange={(event) => setRawInputValue(event.target.value)}
          className="peer h-8 sm:w-[150px] lg:w-[250px]"
          aria-describedby="search-shortcut"
        />

        {hasClearButton && rawInputValue?.length ? (
          <button
            aria-label={t('Clear')}
            className={cn(
              'absolute right-2 h-5 rounded-xl border border-transparent bg-neutral-800/60 px-1 font-mono text-xs',
              'text-neutral-200 peer-focus:opacity-0 light:bg-neutral-200 light:text-neutral-800',
              'cursor-default border border-neutral-800 light:border-neutral-200 lg:block',
            )}
            onClick={handleClearClick}
          >
            <LuX className="h-3 w-3" />
          </button>
        ) : null}

        {hasHotkey ? (
          <BaseTooltip>
            <BaseTooltipTrigger asChild>
              <kbd
                id="search-shortcut"
                className={cn(
                  'absolute right-2 hidden rounded-md border border-transparent bg-neutral-800/60 px-1.5 font-mono text-xs',
                  'text-neutral-400 peer-focus:opacity-0 light:bg-neutral-200 light:text-neutral-800',
                  'cursor-default lg:block',
                )}
              >
                {'/'}
              </kbd>
            </BaseTooltipTrigger>

            <BaseTooltipContent>
              <p>{t('Type / to focus the search field.')}</p>
            </BaseTooltipContent>
          </BaseTooltip>
        ) : null}
      </div>
    </div>
  );
}

function getDebounceDuration(input: string): number {
  // Don't debounce at all when the input is cleared.
  if (input.length === 0) {
    return 0;
  }

  // Use a longer debounce for shorter inputs.
  if (input.length < 3) {
    return 1000;
  }

  // Use a shorter debounce for longer inputs.
  return 500;
}
