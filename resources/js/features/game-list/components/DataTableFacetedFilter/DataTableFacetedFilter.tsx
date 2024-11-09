import type { Column } from '@tanstack/react-table';
import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { HiOutlineCheck } from 'react-icons/hi';
import { RxPlusCircled } from 'react-icons/rx';

import { BaseBadge } from '@/common/components/+vendor/BaseBadge';
import { BaseButton } from '@/common/components/+vendor/BaseButton';
import {
  BaseCommand,
  BaseCommandEmpty,
  BaseCommandGroup,
  BaseCommandInput,
  BaseCommandItem,
  BaseCommandList,
  BaseCommandSeparator,
} from '@/common/components/+vendor/BaseCommand';
import {
  BasePopover,
  BasePopoverContent,
  BasePopoverTrigger,
} from '@/common/components/+vendor/BasePopover';
import { BaseSeparator } from '@/common/components/+vendor/BaseSeparator';
import { buildTrackingClassNames } from '@/common/utils/buildTrackingClassNames';
import { cn } from '@/utils/cn';

interface FacetedFilterOption {
  label: string;
  value: string;
  icon?: React.ComponentType<{ className?: string }>;
  selectedLabel?: string;
}

interface DataTableFacetedFilterProps<TData, TValue> {
  options: FacetedFilterOption[];

  className?: string;
  column?: Column<TData, TValue>;
  isSearchable?: boolean;
  isSingleSelect?: boolean;
  title?: string;
  variant?: 'base' | 'drawer';
}

export function DataTableFacetedFilter<TData, TValue>({
  options,
  column,
  title,
  className,
  isSearchable = true,
  isSingleSelect = false,
  variant = 'base',
}: DataTableFacetedFilterProps<TData, TValue>) {
  const { t } = useTranslation();

  const facets = column?.getFacetedUniqueValues();
  const selectedValues = new Set(column?.getFilterValue() as string[]);

  if (variant === 'drawer') {
    return (
      <div className="flex flex-col gap-1">
        <p className="text-neutral-100 light:text-neutral-950">{title}</p>

        <FacetedFilterContent
          facets={facets}
          options={options}
          selectedValues={selectedValues}
          column={column}
          isSearchable={isSearchable}
          title={title}
          isSingleSelect={isSingleSelect}
          variant={variant}
        />
      </div>
    );
  }

  return (
    <BasePopover>
      <BasePopoverTrigger asChild>
        <BaseButton
          size="sm"
          className={cn(
            'border-dashed',
            buildTrackingClassNames(`Click ${title} Filter`),
            className,
          )}
          data-testid={`filter-${title}`}
        >
          <RxPlusCircled className="mr-2 h-4 w-4" />

          {title}

          {selectedValues?.size > 0 ? (
            <>
              <BaseSeparator orientation="vertical" className="mx-2 h-4" />

              <BaseBadge
                variant="secondary"
                className="rounded-sm px-1 font-normal leading-3 lg:hidden"
              >
                {selectedValues.size}
              </BaseBadge>

              <div className="hidden space-x-1 lg:flex">
                {selectedValues.size > 2 ? (
                  <BaseBadge variant="secondary" className="rounded-sm px-1 font-normal leading-3">
                    {t('{{count, number}} selected', { count: selectedValues.size })}
                  </BaseBadge>
                ) : (
                  <>
                    {options
                      .filter((option) => selectedValues.has(option.value))
                      .map((option) => (
                        <BaseBadge
                          variant="secondary"
                          key={`label-${option.value}`}
                          className="rounded-sm px-1 font-normal leading-3"
                          data-testid="filter-selected-label"
                        >
                          {option.selectedLabel ?? option.label}
                        </BaseBadge>
                      ))}
                  </>
                )}
              </div>
            </>
          ) : null}
        </BaseButton>
      </BasePopoverTrigger>

      <BasePopoverContent className="min-w-[200px] p-0" align="start">
        <FacetedFilterContent
          facets={facets}
          options={options}
          selectedValues={selectedValues}
          column={column}
          isSearchable={isSearchable}
          title={title}
          isSingleSelect={isSingleSelect}
          variant={variant}
        />
      </BasePopoverContent>
    </BasePopover>
  );
}

type FacetedFilterContentProps<TData, TValue> = DataTableFacetedFilterProps<TData, TValue> & {
  facets: Map<unknown, number> | undefined;
  selectedValues: Set<string>;
};

function FacetedFilterContent<TData, TValue>({
  facets,
  options,
  isSingleSelect,
  column,
  isSearchable,
  title,
  selectedValues,
  variant = 'base',
}: FacetedFilterContentProps<TData, TValue>) {
  const { t } = useTranslation();

  const handleOptionToggle = (optionValue: string) => {
    if (isSingleSelect) {
      // For radio button behavior, set the filter to the selected option directly.
      column?.setFilterValue([optionValue]);
    } else {
      // For checkbox behavior, toggle the selection in the set.
      if (selectedValues.has(optionValue)) {
        selectedValues.delete(optionValue);
      } else {
        selectedValues.add(optionValue);
      }

      const filterValues = Array.from(selectedValues);
      column?.setFilterValue(filterValues.length ? filterValues : undefined);
    }
  };

  return (
    <BaseCommand
      className={cn(
        variant === 'drawer' && !isSingleSelect
          ? 'h-[168px] rounded-md border border-neutral-800 light:border-neutral-200'
          : '',
      )}
    >
      {isSearchable && variant !== 'drawer' ? <BaseCommandInput placeholder={title} /> : null}

      <BaseCommandList>
        <BaseCommandEmpty>
          <span className="text-muted">{t('No options found.')}</span>
        </BaseCommandEmpty>

        <BaseCommandGroup>
          {options.map((option) => {
            const isSelected = selectedValues.has(option.value);

            return (
              <BaseCommandItem key={option.value} onSelect={() => handleOptionToggle(option.value)}>
                <div
                  className={cn(
                    'mr-2 flex h-4 w-4 items-center justify-center rounded-sm',
                    'border border-neutral-600 light:border-neutral-900',

                    // If it's a single select, give the appearance of a radio button.
                    isSingleSelect ? 'rounded-full' : 'rounded-sm',

                    isSelected
                      ? 'border-neutral-50 bg-neutral-700 text-neutral-50 light:bg-text'
                      : 'opacity-50 [&_svg]:invisible',
                  )}
                >
                  {isSelected ? <HiOutlineCheck className="h-4 w-4" /> : null}
                </div>

                {option.icon ? (
                  <option.icon className="text-muted-foreground mr-2 h-4 w-4" />
                ) : null}

                <span className="text-neutral-200 light:text-neutral-900">{option.label}</span>

                {facets?.get(option.value) && (
                  <span className="ml-auto flex h-4 w-4 items-center justify-center font-mono text-xs">
                    {facets.get(option.value)}
                  </span>
                )}
              </BaseCommandItem>
            );
          })}
        </BaseCommandGroup>

        {!isSingleSelect && selectedValues.size > 0 ? (
          <ClearFiltersButton onClear={() => column?.setFilterValue(undefined)} />
        ) : null}
      </BaseCommandList>
    </BaseCommand>
  );
}

interface ClearFiltersButtonProps {
  onClear: () => void;
}

const ClearFiltersButton: FC<ClearFiltersButtonProps> = ({ onClear }) => {
  const { t } = useTranslation();

  return (
    <div className="sticky bottom-0 bg-neutral-950 light:bg-neutral-100">
      <BaseCommandSeparator />
      <BaseCommandGroup>
        <BaseCommandItem
          onSelect={onClear}
          className="cursor-pointer justify-center text-center text-xs text-link transition hover:bg-neutral-900 light:text-neutral-900 light:hover:bg-neutral-200"
        >
          {t('Clear filters')}
        </BaseCommandItem>
      </BaseCommandGroup>
    </div>
  );
};
