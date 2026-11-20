import type { VisibilityState } from '@tanstack/react-table';
import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import type { IconType } from 'react-icons';
import { LuArrowDownAZ, LuArrowDownWideNarrow, LuArrowUpNarrowWide } from 'react-icons/lu';
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
import { useHoverIntent } from '@/common/hooks/useHoverIntent';
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
  serverDefaultSortParam: TicketListSortParam;
  sortFields: App.Platform.Enums.TicketListSortField[];
  sortParam: TicketListSortParam;

  onPrefetchSort?: (sortParam: TicketListSortParam) => void;
}

export const TicketListDisplayPanel: FC<TicketListDisplayPanelProps> = ({
  columnDefinitions,
  columnVisibility,
  hasColumnVisibilityOverrides,
  onPrefetchSort,
  onResetDisplay,
  onSortChange,
  onToggleColumn,
  serverDefaultSortParam,
  sortFields,
  sortParam,
}) => {
  const { t } = useTranslation();

  const hoverIntent = useHoverIntent();

  const isAscending = ticketListSort.isAscending(sortParam);
  const sortField = ticketListSort.field(sortParam);

  const DirectionIcon = getSortDirectionIcon(sortField, isAscending);

  const sortFieldLabels: Record<
    App.Platform.Enums.TicketListSortField,
    { label: string; ascendingLabel: string; descendingLabel: string }
  > = {
    createdAt: {
      label: t('Created'),
      ascendingLabel: t('Oldest first'),
      descendingLabel: t('Newest first'),
    },
    state: {
      label: t('Status'),
      ascendingLabel: t('Open first'),
      descendingLabel: t('Closed first'),
    },
    resolvedAt: {
      label: t('Resolved'),
      ascendingLabel: t('Oldest first'),
      descendingLabel: t('Newest first'),
    },
    ticketable: {
      label: t('Issue with'),
      ascendingLabel: t('A to Z'),
      descendingLabel: t('Most tickets first'),
    },
    game: {
      label: t('Game'),
      ascendingLabel: t('A to Z'),
      descendingLabel: t('Most tickets first'),
    },
  };

  const directionLabel = isAscending
    ? sortFieldLabels[sortField].ascendingLabel
    : sortFieldLabels[sortField].descendingLabel;

  const hasDisplayChanges = hasColumnVisibilityOverrides || sortParam !== serverDefaultSortParam;

  const getIsAscendingForField = (field: App.Platform.Enums.TicketListSortField): boolean => {
    if (ticketListSort.isGroupingField(field)) {
      return false;
    }

    if (ticketListSort.isGroupingField(sortField)) {
      return ticketListSort.isAscending(serverDefaultSortParam);
    }

    return isAscending;
  };

  const handleSortFieldChange = (field: App.Platform.Enums.TicketListSortField) => {
    onSortChange(ticketListSort.build(field, getIsAscendingForField(field)));
  };

  return (
    <BasePopover>
      <BasePopoverTrigger asChild>
        <BaseButton
          size="sm"
          aria-label={t('Display')}
          className={cn('relative max-sm:h-9', buildTrackingClassNames('Click Ticket Display'))}
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
                  onMouseEnter={() =>
                    onPrefetchSort?.(ticketListSort.build(sortField, !isAscending))
                  }
                >
                  <DirectionIcon className="size-4" />
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
                {sortFields.map((field) => (
                  <BaseSelectItem
                    key={field}
                    value={field}
                    onMouseEnter={() =>
                      hoverIntent.start(() =>
                        onPrefetchSort?.(
                          ticketListSort.build(field, getIsAscendingForField(field)),
                        ),
                      )
                    }
                    onMouseLeave={hoverIntent.cancel}
                  >
                    {sortFieldLabels[field].label}
                  </BaseSelectItem>
                ))}
              </BaseSelectContent>
            </BaseSelect>
          </div>
        </div>

        <div className="max-sm:hidden">
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
        </div>

        {hasDisplayChanges ? (
          <>
            <BaseSeparator className="my-3" />

            <BaseButton
              size="xs"
              data-testid="reset-display"
              onClick={onResetDisplay}
              onMouseEnter={() => {
                if (sortParam !== serverDefaultSortParam) {
                  onPrefetchSort?.(serverDefaultSortParam);
                }
              }}
            >
              {t('Reset to defaults')}
            </BaseButton>
          </>
        ) : null}
      </BasePopoverContent>
    </BasePopover>
  );
};

// Ascending sort on a grouping field means we're sorting A to Z rather than by fewest tickets first.
function getSortDirectionIcon(
  field: App.Platform.Enums.TicketListSortField,
  isAscending: boolean,
): IconType {
  if (!isAscending) {
    return LuArrowDownWideNarrow;
  }

  return ticketListSort.isGroupingField(field) ? LuArrowDownAZ : LuArrowUpNarrowWide;
}
