import type { ColumnFiltersState, Updater } from '@tanstack/react-table';
import { type FC, useState } from 'react';
import { Trans, useTranslation } from 'react-i18next';
import { RxCross2 } from 'react-icons/rx';

import {
  BaseDropdownMenu,
  BaseDropdownMenuContent,
  BaseDropdownMenuTrigger,
} from '@/common/components/+vendor/BaseDropdownMenu';
import { cn } from '@/common/utils/cn';

import type { TicketListFilterProperty } from '../../models';
import { getActiveTicketListFilterProperties } from '../../utils/getActiveTicketListFilterProperties';
import { setTicketListColumnFilterValue } from '../../utils/setTicketListColumnFilterValue';
import { TicketListFilterValueList } from '../TicketListFilterControl/TicketListFilterValueList';

interface TicketListFilterChipsProps {
  columnFilters: ColumnFiltersState;
  properties: TicketListFilterProperty[];
  setColumnFilters: (updaterOrValue: Updater<ColumnFiltersState>) => void;
}

export const TicketListFilterChips: FC<TicketListFilterChipsProps> = ({
  columnFilters,
  properties,
  setColumnFilters,
}) => {
  const { t } = useTranslation();

  const [openPropertyId, setOpenPropertyId] = useState<string | null>(null);

  const activeProperties = getActiveTicketListFilterProperties(properties, columnFilters);

  if (!activeProperties.length) {
    return null;
  }

  const handleValueChange = (property: TicketListFilterProperty, value: string) => {
    setColumnFilters((previousFilters) =>
      setTicketListColumnFilterValue(previousFilters, property.id, value),
    );

    setOpenPropertyId(null);
  };

  const segmentClassNames =
    'flex h-full items-center px-2 whitespace-nowrap hover:bg-neutral-800 light:hover:bg-neutral-100';

  return (
    <>
      {activeProperties.map(({ property, value }) => {
        const selectedOption = property.options.find((option) => option.value === value);

        return (
          <div
            key={property.id}
            data-testid={`chip-${property.id}`}
            className={cn(
              'flex h-7.5 items-center divide-x divide-neutral-700 rounded-md border border-neutral-700 text-[13px]',
              'bg-neutral-950 light:divide-neutral-200 light:border-neutral-200 light:bg-white',
            )}
          >
            <BaseDropdownMenu
              open={openPropertyId === property.id}
              onOpenChange={(nextIsOpen) => setOpenPropertyId(nextIsOpen ? property.id : null)}
            >
              <span
                className={cn(
                  'flex h-full items-center gap-1 pl-2 whitespace-nowrap',
                  'text-neutral-400 light:text-neutral-600',
                )}
              >
                <Trans
                  i18nKey="{{label}} is <1>{{value}}</1>"
                  values={{ label: property.label, value: selectedOption?.label ?? value }}
                  components={{
                    1: (
                      <BaseDropdownMenuTrigger
                        aria-label={t('Change {{label}} filter', { label: property.label })}
                        data-testid={`chip-${property.id}-value`}
                        className={cn(
                          segmentClassNames,
                          'font-medium text-neutral-50 light:text-neutral-900',
                        )}
                      />
                    ),
                  }}
                />
              </span>

              <BaseDropdownMenuContent align="start" className="min-w-64 p-0">
                <TicketListFilterValueList
                  property={property}
                  selectedValue={value}
                  onSelect={(nextValue) => handleValueChange(property, nextValue)}
                />
              </BaseDropdownMenuContent>
            </BaseDropdownMenu>

            <button
              type="button"
              aria-label={t('Remove {{label}} filter', { label: property.label })}
              onClick={() => handleValueChange(property, property.noFilterValue)}
              className={cn(
                segmentClassNames,
                'rounded-r-md text-neutral-400 hover:text-neutral-50 light:text-neutral-600',
              )}
            >
              <RxCross2 className="size-3.5" />
            </button>
          </div>
        );
      })}
    </>
  );
};
