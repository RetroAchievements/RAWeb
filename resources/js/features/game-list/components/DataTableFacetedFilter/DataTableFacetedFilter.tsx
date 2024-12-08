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
import {
  BaseTooltip,
  BaseTooltipContent,
  BaseTooltipTrigger,
} from '@/common/components/+vendor/BaseTooltip';
import { buildTrackingClassNames } from '@/common/utils/buildTrackingClassNames';
import { cn } from '@/common/utils/cn';
import type { TranslatedString } from '@/types/i18next';

interface FacetedFilterOption<TValue = string> {
  t_label: TranslatedString;

  icon?: React.ComponentType<{ className?: string }>;
  isDefaultOption?: boolean;
  selectedLabel?: string;
  t_description?: TranslatedString;
  value?: TValue;
}

interface FacetedFilterOptionGroup<TValue = string> {
  options: FacetedFilterOption<TValue>[];

  t_heading?: TranslatedString;
}

export type FilterOptions<TValue = string> =
  | FacetedFilterOption<TValue>[]
  | FacetedFilterOptionGroup<TValue>[];

interface DataTableFacetedFilterProps<TData, TValue> {
  options: FilterOptions;

  baseCommandListClassName?: string;
  className?: string;
  column?: Column<TData, TValue>;
  disabled?: boolean;
  isSearchable?: boolean;
  isSingleSelect?: boolean;
  t_title?: TranslatedString;
  variant?: 'base' | 'drawer';
}

export function DataTableFacetedFilter<TData, TValue>({
  baseCommandListClassName,
  className,
  column,
  disabled,
  options,
  t_title,
  isSearchable = true,
  isSingleSelect = false,
  variant = 'base',
}: DataTableFacetedFilterProps<TData, TValue>) {
  const { t } = useTranslation();

  const facets = column?.getFacetedUniqueValues();
  const selectedValues = new Set(column?.getFilterValue() as string[]);
  const columnId = column!.id;
  const allFlatOptions = getAllFlatOptions(options);

  if (variant === 'drawer') {
    return (
      <div className="flex flex-col gap-1">
        <p className="text-neutral-100 light:text-neutral-950">{t_title}</p>

        <FacetedFilterContent
          facets={facets}
          options={options}
          selectedValues={selectedValues}
          column={column}
          isSearchable={isSearchable}
          t_title={t_title}
          isSingleSelect={isSingleSelect}
          variant={variant}
        />
      </div>
    );
  }

  return (
    <BaseTooltip open={!disabled ? false : undefined}>
      <BasePopover>
        <BaseTooltipTrigger asChild>
          <BasePopoverTrigger asChild>
            <BaseButton
              size="sm"
              className={cn(
                'border-dashed',
                buildTrackingClassNames(`Click ${columnId} Filter`),
                disabled ? '!pointer-events-auto' : null,
                className,
              )}
              disabled={disabled}
              data-testid={`filter-${columnId}`}
            >
              <RxPlusCircled className="mr-2 size-4" />

              {t_title}

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
                      <BaseBadge
                        variant="secondary"
                        className="rounded-sm px-1 font-normal leading-3"
                      >
                        {t('{{count, number}} selected', { count: selectedValues.size })}
                      </BaseBadge>
                    ) : (
                      <>
                        {allFlatOptions
                          .filter((option) => option.value && selectedValues.has(option.value))
                          .map((option) => (
                            <BaseBadge
                              variant="secondary"
                              key={`label-${option.value}`}
                              className="rounded-sm px-1 font-normal leading-3"
                              data-testid="filter-selected-label"
                            >
                              {option.selectedLabel ?? option.t_label}
                            </BaseBadge>
                          ))}
                      </>
                    )}
                  </div>
                </>
              ) : null}
            </BaseButton>
          </BasePopoverTrigger>
        </BaseTooltipTrigger>

        <BasePopoverContent className="min-w-[340px] p-0" align="start">
          <FacetedFilterContent
            baseCommandListClassName={baseCommandListClassName}
            facets={facets}
            options={options}
            selectedValues={selectedValues}
            column={column}
            isSearchable={isSearchable}
            t_title={t_title}
            isSingleSelect={isSingleSelect}
            variant={variant}
          />
        </BasePopoverContent>
      </BasePopover>

      <BaseTooltipContent>
        <p className="text-sm">{t('Sign in to use this filter.')}</p>
      </BaseTooltipContent>
    </BaseTooltip>
  );
}

type FacetedFilterContentProps<TData, TValue> = DataTableFacetedFilterProps<TData, TValue> & {
  facets: Map<unknown, number> | undefined;
  isSingleSelect: boolean;
  selectedValues: Set<string>;
};

function FacetedFilterContent<TData, TValue>({
  baseCommandListClassName,
  column,
  facets,
  isSearchable,
  isSingleSelect,
  options,
  selectedValues,
  t_title,
  variant = 'base',
}: FacetedFilterContentProps<TData, TValue>) {
  const { t } = useTranslation();

  const handleOptionToggle = (option: FacetedFilterOption) => {
    if (isSingleSelect) {
      if (option.isDefaultOption) {
        // Clear the filter when the default option is selected.
        column?.setFilterValue(undefined);
      } else {
        // For radio button behavior, set the filter to the selected option directly.
        column?.setFilterValue([option.value]);
      }
    } else {
      // For checkbox behavior, toggle the selection in the set.
      if (option.value) {
        if (selectedValues.has(option.value)) {
          selectedValues.delete(option.value);
        } else {
          selectedValues.add(option.value);
        }

        const filterValues = Array.from(selectedValues);
        column?.setFilterValue(filterValues.length ? filterValues : undefined);
      }
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
      {isSearchable && variant !== 'drawer' ? <BaseCommandInput placeholder={t_title} /> : null}

      <BaseCommandList className={baseCommandListClassName}>
        <BaseCommandEmpty>
          <span className="text-muted">{t('No options found.')}</span>
        </BaseCommandEmpty>

        {isOptionGroupArray(options) ? (
          options.map((group, index) => (
            <BaseCommandGroup key={`${group.t_heading}-${index}`} heading={group.t_heading}>
              {group.options.map((option, optionIndex) => (
                <FilterOption
                  key={`${option.value}-${optionIndex}`}
                  option={option}
                  isSelected={
                    option.isDefaultOption
                      ? !selectedValues.size
                      : selectedValues.has(option.value!)
                  }
                  isSingleSelect={isSingleSelect}
                  facets={facets}
                  onToggle={() => handleOptionToggle(option)}
                />
              ))}

              {index < options.length - 1 ? <BaseCommandSeparator className="mt-2" /> : null}
            </BaseCommandGroup>
          ))
        ) : (
          <BaseCommandGroup>
            {options.map((option) => (
              <FilterOption
                key={option.value}
                option={option}
                isSelected={
                  option.isDefaultOption ? !selectedValues.size : selectedValues.has(option.value!)
                }
                isSingleSelect={isSingleSelect}
                facets={facets}
                onToggle={() => handleOptionToggle(option)}
              />
            ))}
          </BaseCommandGroup>
        )}

        {!isSingleSelect && selectedValues.size > 0 ? (
          <ClearFiltersButton onClear={() => column?.setFilterValue(undefined)} />
        ) : null}
      </BaseCommandList>
    </BaseCommand>
  );
}

interface FilterOptionProps {
  option: FacetedFilterOption;
  isSelected: boolean;
  isSingleSelect: boolean;
  facets: Map<unknown, number> | undefined;
  onToggle: () => void;
}

const FilterOption: FC<FilterOptionProps> = ({
  option,
  isSelected,
  isSingleSelect,
  facets,
  onToggle,
}) => {
  return (
    <BaseCommandItem
      key={option.value}
      onSelect={onToggle}
      className={isSingleSelect ? 'items-start' : undefined}
    >
      <div
        className={cn(
          'mr-2 flex size-4 min-w-4 items-center justify-center rounded-sm',
          'border border-neutral-600 light:border-neutral-900',

          // If it's a single select, give the appearance of a radio button.
          isSingleSelect ? 'mt-[3px] rounded-full' : 'rounded-sm',

          isSelected
            ? 'border-neutral-50 bg-neutral-700 text-neutral-50 light:bg-text'
            : 'opacity-50 [&_svg]:invisible',
        )}
        data-testid="filter-option-indicator"
      >
        {isSelected && <HiOutlineCheck role="img" aria-hidden={true} className="size-4" />}
      </div>

      {option.icon ? (
        <option.icon
          className={cn(
            'mr-2 size-4 min-w-4 text-neutral-200 light:text-neutral-900',
            isSingleSelect ? 'mt-[3px]' : null,
          )}
          data-testid="option-icon"
        />
      ) : null}

      <span className="flex flex-col">
        <span className="text-neutral-200 light:text-neutral-900">{option.t_label}</span>
        {option.t_description ? (
          <span className="text-2xs text-neutral-400">{option.t_description}</span>
        ) : null}
      </span>

      {facets?.get(option.value) && (
        <span className="ml-auto flex size-4 items-center justify-center font-mono text-xs">
          {facets.get(option.value)}
        </span>
      )}
    </BaseCommandItem>
  );
};

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

/**
 * @returns `true` if filter options are organized using groups. `false` if it's a flat list of options.
 */
function isOptionGroupArray(options: FilterOptions): options is FacetedFilterOptionGroup[] {
  return Array.isArray(options) && options.length > 0 && 'options' in options[0];
}

/**
 * @returns All options as a flat array from either grouped or ungrouped options.
 */
function getAllFlatOptions(options: FilterOptions): FacetedFilterOption[] {
  if (isOptionGroupArray(options)) {
    return options.flatMap((group) => group.options);
  }

  return options;
}
