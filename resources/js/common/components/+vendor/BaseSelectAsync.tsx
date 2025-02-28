import type { UseQueryResult } from '@tanstack/react-query';
import type { Dispatch, SetStateAction } from 'react';
import { useCallback, useMemo, useRef, useState } from 'react';
import { LuCheck, LuChevronsUpDown, LuLoader2, LuSearch } from 'react-icons/lu';
import { useDebounce } from 'react-use';

import { cn } from '@/common/utils/cn';
import type { TranslatedString } from '@/types/i18next';

import { BaseButton } from './BaseButton';
import {
  BaseCommand,
  BaseCommandEmpty,
  BaseCommandGroup,
  BaseCommandItem,
  BaseCommandList,
} from './BaseCommand';
import { BaseInput } from './BaseInput';
import { BasePopover, BasePopoverContent, BasePopoverTrigger } from './BasePopover';

export interface QuerySelectProps<T> {
  /** The React Query result object. */
  query: UseQueryResult<T[]> & {
    searchTerm: string;
    setSearchTerm: Dispatch<SetStateAction<string>>;
  };
  /** Function to render each option. */
  renderOption: (option: T) => React.ReactNode;
  /** Function to get the value from an option. */
  getOptionValue: (option: T) => string;
  /** Function to get the display value for the selected option. */
  getDisplayValue: (option: T) => React.ReactNode;
  /** Custom not found message. */
  notFound?: React.ReactNode;
  /** Custom loading skeleton. */
  loadingSkeleton?: React.ReactNode;
  /** Currently selected value. */
  value: string;
  /** Callback when selection changes. */
  onChange: (value: string) => void;
  /** Placeholder text when no selection. */
  placeholder: TranslatedString;
  /** Placeholder to show in the search popover input. */
  popoverPlaceholder: TranslatedString;
  /** Disable the entire select. */
  disabled?: boolean;
  /** Custom width for the popover. */
  width?: string | number;
  /** Custom class names. */
  className?: string;
  /** Custom trigger button class names. */
  triggerClassName?: string;
  /** Custom no results message. */
  noResultsMessage: TranslatedString;
  /** Allow clearing the selection. */
  clearable?: boolean;
  /** Define an initial value the control will have. */
  selectedOption?: T | null;
}

export function BaseSelectAsync<T>({
  query,
  renderOption,
  getOptionValue,
  getDisplayValue,
  notFound,
  loadingSkeleton,
  placeholder,
  value,
  onChange,
  disabled = false,
  width = '200px',
  className,
  triggerClassName,
  noResultsMessage,
  clearable = true,
  popoverPlaceholder = placeholder,
  selectedOption = null,
}: QuerySelectProps<T>) {
  const [open, setOpen] = useState(false);
  const [internalSelectedOption, setInternalSelectedOption] = useState<T | null>(selectedOption);
  const [localSearchTerm, setLocalSearchTerm] = useState(query.searchTerm);
  const isInitialLoad = useRef(true);

  const options = useMemo(() => query.data || [], [query.data]);

  useDebounce(
    () => {
      if (localSearchTerm !== query.searchTerm) {
        isInitialLoad.current = false;
        query.setSearchTerm(localSearchTerm);
      }
    },
    400,
    [localSearchTerm],
  );

  const handleSelect = useCallback(
    (currentValue: string) => {
      const newValue = clearable && currentValue === value ? '' : currentValue;
      setInternalSelectedOption(
        options.find((option) => getOptionValue(option) === newValue) || null,
      );
      onChange(newValue);
      setOpen(false);
    },
    [value, onChange, clearable, options, getOptionValue],
  );

  return (
    <BasePopover open={open} onOpenChange={setOpen}>
      <BasePopoverTrigger asChild>
        <BaseButton
          variant="outline"
          role="combobox"
          aria-expanded={open}
          className={cn(
            'h-10 justify-between rounded-md px-3',
            'bg:neutral-950 light:bg-white',
            'lg:transition-none lg:active:translate-y-0 lg:active:scale-100',

            internalSelectedOption
              ? 'text-neutral-300 light:text-neutral-700'
              : 'text-neutral-400 light:text-neutral-600',
            disabled && 'cursor-not-allowed opacity-50',
            triggerClassName,
          )}
          disabled={
            disabled || (query.isLoading && (!internalSelectedOption || !isInitialLoad.current))
          }
        >
          {internalSelectedOption ? getDisplayValue(internalSelectedOption) : placeholder}

          <LuChevronsUpDown className="size-3" />
        </BaseButton>
      </BasePopoverTrigger>

      <BasePopoverContent style={{ width }} className={cn('p-0', className)} align="start">
        <BaseCommand>
          <div className="relative w-full">
            <LuSearch className="absolute left-2 top-1/2 h-4 w-4 -translate-y-1/2 transform text-neutral-500" />
            <BaseInput
              placeholder={popoverPlaceholder}
              value={localSearchTerm}
              onChange={(e) => setLocalSearchTerm(e.target.value)}
              className="flex-1 rounded-b-none border-none pl-8 focus-visible:ring-0"
            />
            {query.isFetching && (
              <div className="absolute right-2 top-1/2 flex -translate-y-1/2 transform items-center">
                <LuLoader2 className="h-4 w-4 animate-spin" />
              </div>
            )}
          </div>
          <BaseCommandList>
            {query.error && (
              <div className="text-destructive p-4 text-center">
                {query.error instanceof Error ? query.error.message : 'Failed to fetch options'}
              </div>
            )}

            {query.isLoading && !query.data && (loadingSkeleton || <DefaultLoadingSkeleton />)}

            {!query.isLoading &&
              !query.error &&
              options.length === 0 &&
              (notFound || (
                <BaseCommandEmpty className="py-4 text-center text-sm text-neutral-700">
                  {noResultsMessage}
                </BaseCommandEmpty>
              ))}

            <BaseCommandGroup>
              {options.map((option) => (
                <BaseCommandItem
                  key={getOptionValue(option)}
                  value={getOptionValue(option)}
                  onSelect={handleSelect}
                  className="text-neutral-300 light:text-neutral-950"
                >
                  {renderOption(option)}
                  <LuCheck
                    className={cn(
                      'ml-auto h-3 w-3',
                      value === getOptionValue(option) ? 'opacity-100' : 'opacity-0',
                    )}
                  />
                </BaseCommandItem>
              ))}
            </BaseCommandGroup>
          </BaseCommandList>
        </BaseCommand>
      </BasePopoverContent>
    </BasePopover>
  );
}

function DefaultLoadingSkeleton() {
  return (
    <BaseCommandGroup>
      {[1, 2, 3].map((i) => (
        <BaseCommandItem key={i} disabled>
          <div className="flex w-full items-center gap-2">
            <div className="bg-muted h-6 w-6 animate-pulse rounded-full" />
            <div className="flex flex-1 flex-col gap-1">
              <div className="bg-muted h-4 w-24 animate-pulse rounded" />
              <div className="bg-muted h-3 w-16 animate-pulse rounded" />
            </div>
          </div>
        </BaseCommandItem>
      ))}
    </BaseCommandGroup>
  );
}
