import type { Table } from '@tanstack/react-table';
import { useEffect, useState } from 'react';
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
}

export function DataTableSearchInput<TData>({ table }: DataTableSearchInputProps<TData>) {
  const initialValue = (table.getColumn('title')?.getFilterValue() as string) ?? '';

  const [rawInputValue, setRawInputValue] = useState(initialValue);

  const { hotkeyInputRef } = useSearchInputHotkey({ key: '/' });

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
      table.getColumn('title')?.setFilterValue(rawInputValue);
    },
    getDebounceDuration(rawInputValue),
    [rawInputValue],
  );

  return (
    <div className="w-full sm:w-auto">
      <label htmlFor="search-field" className="sr-only">
        Search games
      </label>

      <div className="group relative flex items-center">
        <BaseInput
          id="search-field"
          ref={hotkeyInputRef}
          placeholder="Search games..."
          value={rawInputValue}
          onChange={(event) => setRawInputValue(event.target.value)}
          className="peer h-8 sm:w-[150px] lg:w-[250px]"
          aria-describedby="search-shortcut"
        />

        <BaseTooltip>
          <BaseTooltipTrigger asChild>
            <kbd
              id="search-shortcut"
              className={cn(
                'absolute right-2 hidden rounded-md border border-transparent bg-neutral-800/60 px-1.5 font-mono text-xs',
                'text-neutral-400 peer-focus:opacity-0 light:bg-gray-200 light:text-gray-800',
                'cursor-default lg:block',
              )}
            >
              /
            </kbd>
          </BaseTooltipTrigger>

          <BaseTooltipContent>
            <p>Type / to focus the search field.</p>
          </BaseTooltipContent>
        </BaseTooltip>
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
    return 500;
  }

  // Use a shorter debounce for longer inputs.
  return 200;
}
