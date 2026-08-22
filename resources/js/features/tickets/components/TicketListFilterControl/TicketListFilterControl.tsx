import type { ColumnFiltersState, Updater } from '@tanstack/react-table';
import { type FC, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { RxPlusCircled } from 'react-icons/rx';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import {
  BaseDropdownMenu,
  BaseDropdownMenuContent,
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

  const [isOpen, setIsOpen] = useState(false);

  const handleValueSelect = (property: TicketListFilterProperty, value: string) => {
    setColumnFilters((previousFilters) =>
      setTicketListColumnFilterValue(previousFilters, property.id, value),
    );

    setIsOpen(false);
  };

  return (
    <BaseDropdownMenu open={isOpen} onOpenChange={setIsOpen}>
      <BaseDropdownMenuTrigger asChild>
        <BaseButton
          size="sm"
          aria-label={t('Filter')}
          className={cn(
            'border-dashed bg-neutral-950 light:bg-white',
            buildTrackingClassNames('Click Ticket Filter'),
          )}
          data-testid="add-filter"
        >
          <RxPlusCircled className={cn('size-4', isLabelHidden ? null : 'mr-2')} />
          {isLabelHidden ? null : t('Filter')}
        </BaseButton>
      </BaseDropdownMenuTrigger>

      <BaseDropdownMenuContent align="start" className="min-w-48">
        {properties.map((property) => (
          <BaseDropdownMenuSub key={property.id}>
            <BaseDropdownMenuSubTrigger data-testid={`filter-property-${property.id}`}>
              {property.label}
            </BaseDropdownMenuSubTrigger>

            <BaseDropdownMenuSubContent className="min-w-64 p-0">
              <TicketListFilterValueList
                property={property}
                selectedValue={
                  getTicketListFilterValue(columnFilters, property.id) ?? property.noFilterValue
                }
                onSelect={(value) => handleValueSelect(property, value)}
              />
            </BaseDropdownMenuSubContent>
          </BaseDropdownMenuSub>
        ))}
      </BaseDropdownMenuContent>
    </BaseDropdownMenu>
  );
};
