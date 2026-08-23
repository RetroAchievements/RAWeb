import type { VisibilityState } from '@tanstack/react-table';
import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuArrowDownWideNarrow, LuArrowUpNarrowWide } from 'react-icons/lu';
import { RxMixerHorizontal } from 'react-icons/rx';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import {
  BasePopover,
  BasePopoverContent,
  BasePopoverTrigger,
} from '@/common/components/+vendor/BasePopover';
import {
  BaseSelect,
  BaseSelectContent,
  BaseSelectItem,
  BaseSelectTrigger,
  BaseSelectValue,
} from '@/common/components/+vendor/BaseSelect';
import { BaseSeparator } from '@/common/components/+vendor/BaseSeparator';
import { BaseToggle } from '@/common/components/+vendor/BaseToggle';
import {
  BaseTooltip,
  BaseTooltipContent,
  BaseTooltipTrigger,
} from '@/common/components/+vendor/BaseTooltip';
import { buildTrackingClassNames } from '@/common/utils/buildTrackingClassNames';
import { cn } from '@/common/utils/cn';

import type {
  TicketListColumnDefinition,
  TicketListColumnId,
  TicketListSortParam,
} from '../../models';
import { ticketListSort } from '../../utils/ticketListSort';

interface TicketListDisplayPanelProps {
  columnDefinitions: TicketListColumnDefinition[];
  columnVisibility: VisibilityState;
  hasColumnVisibilityOverrides: boolean;
  onResetDisplay: () => void;
  onSortChange: (sortParam: TicketListSortParam) => void;
  onToggleColumn: (columnId: TicketListColumnId) => void;
  sortParam: TicketListSortParam;
}

export const TicketListDisplayPanel: FC<TicketListDisplayPanelProps> = ({
  columnDefinitions,
  columnVisibility,
  hasColumnVisibilityOverrides,
  onResetDisplay,
  onSortChange,
  onToggleColumn,
  sortParam,
}) => {
  const { t } = useTranslation();

  const isAscending = ticketListSort.isAscending(sortParam);
  const sortField = ticketListSort.field(sortParam);

  let directionLabel = isAscending ? t('Oldest first') : t('Newest first');
  if (sortField === 'state') {
    directionLabel = isAscending ? t('Ascending') : t('Descending');
  }

  const sortFieldLabels = {
    createdAt: t('Created'),
    state: t('Status'),
    resolvedAt: t('Resolution date'),
  };

  const hasDisplayChanges =
    hasColumnVisibilityOverrides || sortParam !== ticketListSort.defaultParam;

  const handleSortFieldChange = (field: App.Platform.Enums.TicketListSortField) => {
    onSortChange(ticketListSort.build(field, isAscending));
  };

  return (
    <BasePopover>
      <BasePopoverTrigger asChild>
        <BaseButton
          size="sm"
          aria-label={t('Display')}
          className={cn('relative', buildTrackingClassNames('Click Ticket Display'))}
          data-testid="open-display-panel"
        >
          <RxMixerHorizontal className="size-4" />

          {hasDisplayChanges ? (
            <span
              aria-hidden="true"
              data-testid="display-changed-dot"
              className="absolute -top-1 -right-1 size-2 rounded-full bg-link"
            />
          ) : null}
        </BaseButton>
      </BasePopoverTrigger>

      <BasePopoverContent
        align="end"
        className="w-72 p-3"
        onOpenAutoFocus={(event) => event.preventDefault()}
      >
        <div className="flex items-center justify-between gap-2">
          <span className="text-neutral-200 light:text-neutral-900">{t('Sort')}</span>

          <div className="flex items-center gap-1">
            <BaseTooltip>
              <BaseTooltipTrigger asChild>
                <BaseButton
                  size="sm"
                  variant="ghost"
                  aria-label={directionLabel}
                  className="size-8 flex-none p-0"
                  data-testid="toggle-sort-direction"
                  onClick={() => onSortChange(ticketListSort.build(sortField, !isAscending))}
                >
                  {isAscending ? (
                    <LuArrowUpNarrowWide className="size-4" />
                  ) : (
                    <LuArrowDownWideNarrow className="size-4" />
                  )}
                </BaseButton>
              </BaseTooltipTrigger>

              <BaseTooltipContent>{directionLabel}</BaseTooltipContent>
            </BaseTooltip>

            <BaseSelect value={sortField} onValueChange={handleSortFieldChange}>
              <BaseSelectTrigger
                aria-label={t('Sort by')}
                className="h-8 w-36"
                data-testid="sort-field"
              >
                <BaseSelectValue />
              </BaseSelectTrigger>

              <BaseSelectContent>
                {ticketListSort.fields.map((field) => (
                  <BaseSelectItem key={field} value={field}>
                    {sortFieldLabels[field]}
                  </BaseSelectItem>
                ))}
              </BaseSelectContent>
            </BaseSelect>
          </div>
        </div>

        <BaseSeparator className="my-3" />

        <p className="mb-2 text-neutral-400 light:text-neutral-600">{t('Columns')}</p>

        <div className="flex flex-wrap gap-1.5">
          {columnDefinitions
            .filter((columnDefinition) => columnDefinition.enableHiding !== false)
            .map((columnDefinition) => (
              <BaseToggle
                key={columnDefinition.id}
                pressed={columnVisibility[columnDefinition.id]}
                data-testid={`column-toggle-${columnDefinition.id}`}
                onPressedChange={() => onToggleColumn(columnDefinition.id)}
                className={cn(
                  'h-auto rounded-full border border-neutral-800 px-2.5 py-1',
                  'text-xs font-normal text-neutral-500',
                  'data-[state=on]:border-neutral-600 data-[state=on]:bg-neutral-700',
                  'data-[state=on]:text-neutral-100 light:border-neutral-200 light:text-neutral-400',
                  'data-[state=on]:light:border-neutral-300 data-[state=on]:light:bg-neutral-200 data-[state=on]:light:text-neutral-900',
                )}
              >
                {columnDefinition.meta.t_label}
              </BaseToggle>
            ))}
        </div>

        {hasDisplayChanges ? (
          <>
            <BaseSeparator className="my-3" />

            <BaseButton size="xs" data-testid="reset-display" onClick={onResetDisplay}>
              {t('Reset to defaults')}
            </BaseButton>
          </>
        ) : null}
      </BasePopoverContent>
    </BasePopover>
  );
};
