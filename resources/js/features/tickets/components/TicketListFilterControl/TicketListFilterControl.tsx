import type { ColumnFiltersState, Updater } from '@tanstack/react-table';
import { type FC, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { LuChevronLeft, LuChevronRight } from 'react-icons/lu';
import { RxPlusCircled } from 'react-icons/rx';
import { useMedia } from 'react-use';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import {
  BaseDropdownMenu,
  BaseDropdownMenuContent,
  BaseDropdownMenuItem,
  BaseDropdownMenuSeparator,
  BaseDropdownMenuSub,
  BaseDropdownMenuSubContent,
  BaseDropdownMenuSubTrigger,
  BaseDropdownMenuTrigger,
} from '@/common/components/+vendor/BaseDropdownMenu';
import { buildTrackingClassNames } from '@/common/utils/buildTrackingClassNames';
import { cn } from '@/common/utils/cn';

import type { TicketListFilterProperty } from '../../models';
import { getTicketListFilterValue } from '../../utils/getTicketListFilterValue';
import { setTicketListColumnFilterValue } from '../../utils/setTicketListColumnFilterValue';
import { TicketListFilterValueList } from './TicketListFilterValueList';

interface TicketListFilterControlProps {
  columnFilters: ColumnFiltersState;
  properties: TicketListFilterProperty[];
  setColumnFilters: (updaterOrValue: Updater<ColumnFiltersState>) => void;

  isLabelHidden?: boolean;
}

export const TicketListFilterControl: FC<TicketListFilterControlProps> = ({
  columnFilters,
  properties,
  setColumnFilters,
  isLabelHidden = false,
}) => {
  const { t } = useTranslation();

  const isMobile = useMedia('(max-width: 639px)', false); // also applies to narrow viewports

  const [isOpen, setIsOpen] = useState(false);
  const [selectedFilterId, setSelectedFilterId] = useState<string | null>(null);

  const selectedFilter = properties.find((property) => property.id === selectedFilterId) ?? null;

  const handleOpenChange = (nextIsOpen: boolean) => {
    setIsOpen(nextIsOpen);

    if (!nextIsOpen) {
      setSelectedFilterId(null);
    }
  };

  const handleValueSelect = (property: TicketListFilterProperty, value: string) => {
    setColumnFilters((previousFilters) =>
      setTicketListColumnFilterValue(previousFilters, property.id, value),
    );

    handleOpenChange(false);
  };

  const getSelectedValue = (property: TicketListFilterProperty) =>
    getTicketListFilterValue(columnFilters, property.id) ?? property.noFilterValue;

  return (
    <BaseDropdownMenu open={isOpen} onOpenChange={handleOpenChange}>
      <BaseDropdownMenuTrigger asChild>
        <BaseButton
          size="sm"
          aria-label={t('Filter')}
          className={cn(
            'border-dashed bg-neutral-950 max-sm:h-9 light:bg-white',
            buildTrackingClassNames('Click Ticket Filter'),
          )}
          data-testid="add-filter"
        >
          <RxPlusCircled className="size-4" />
          <span className={cn('ml-2', isLabelHidden ? 'sm:hidden' : null)}>{t('Filter')}</span>
        </BaseButton>
      </BaseDropdownMenuTrigger>

      {isMobile ? (
        <BaseDropdownMenuContent
          align="start"
          collisionPadding={16}
          className={cn('w-[calc(100vw-2rem)] max-w-96', selectedFilter ? 'p-0' : null)}
        >
          {selectedFilter ? (
            <>
              <BaseDropdownMenuItem
                data-testid="filter-back"
                className="m-1 gap-2 py-2 font-medium"
                onSelect={(event) => {
                  event.preventDefault();
                  setSelectedFilterId(null);
                }}
              >
                <LuChevronLeft className="size-4" aria-hidden={true} />
                {selectedFilter.label}
              </BaseDropdownMenuItem>

              <BaseDropdownMenuSeparator />

              <TicketListFilterValueList
                property={selectedFilter}
                selectedValue={getSelectedValue(selectedFilter)}
                onSelect={(value) => handleValueSelect(selectedFilter, value)}
              />
            </>
          ) : (
            properties.map((property) => (
              <BaseDropdownMenuItem
                key={property.id}
                data-testid={`filter-property-${property.id}`}
                className="gap-2 py-2"
                onSelect={(event) => {
                  event.preventDefault();
                  setSelectedFilterId(property.id);
                }}
              >
                {property.label}

                <PropertyActiveDot
                  property={property}
                  isActive={getSelectedValue(property) !== property.noFilterValue}
                />

                <LuChevronRight
                  className="ml-auto size-4 text-neutral-500 light:text-neutral-400"
                  aria-hidden={true}
                />
              </BaseDropdownMenuItem>
            ))
          )}
        </BaseDropdownMenuContent>
      ) : (
        <BaseDropdownMenuContent align="start" className="min-w-48">
          {properties.map((property) => {
            const selectedValue = getSelectedValue(property);

            return (
              <BaseDropdownMenuSub key={property.id}>
                <BaseDropdownMenuSubTrigger
                  data-testid={`filter-property-${property.id}`}
                  className="gap-2"
                >
                  {property.label}

                  <PropertyActiveDot
                    property={property}
                    isActive={selectedValue !== property.noFilterValue}
                  />
                </BaseDropdownMenuSubTrigger>

                <BaseDropdownMenuSubContent className="min-w-64 p-0">
                  <TicketListFilterValueList
                    property={property}
                    selectedValue={selectedValue}
                    onSelect={(value) => handleValueSelect(property, value)}
                  />
                </BaseDropdownMenuSubContent>
              </BaseDropdownMenuSub>
            );
          })}
        </BaseDropdownMenuContent>
      )}
    </BaseDropdownMenu>
  );
};

interface PropertyActiveDotProps {
  isActive: boolean;
  property: TicketListFilterProperty;
}

const PropertyActiveDot: FC<PropertyActiveDotProps> = ({ isActive, property }) => {
  const { t } = useTranslation();

  if (!isActive) {
    return null;
  }

  return (
    <span
      role="img"
      aria-label={t('Active')}
      data-testid={`filter-property-${property.id}-active`}
      className="size-1.5 rounded-full bg-neutral-400 light:bg-neutral-500"
    />
  );
};
