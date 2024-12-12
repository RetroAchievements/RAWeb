import type { Row, Table } from '@tanstack/react-table';
import { useEffect, useRef } from 'react';
import { useTranslation } from 'react-i18next';

import {
  BaseTable,
  BaseTableBody,
  BaseTableCell,
  BaseTableRow,
} from '@/common/components/+vendor/BaseTable';
import { cn } from '@/common/utils/cn';

import { GameRow } from './GameRow';
import { SystemHeaderRow } from './SystemHeaderRow';
import { TableHeader } from './TableHeader';

interface GameListDataTableProps {
  table: Table<App.Platform.Data.GameListEntry>;

  /** Affects row grouping behavior and whether or not a loading state is displayed. */
  isLoading?: boolean;
}

export function GameListDataTable({ table, isLoading = false }: GameListDataTableProps) {
  const { t } = useTranslation();

  const wasShowingGroups = useRef(false);
  const lastSortState = useRef(false);

  const visibleColumnCount = table.getVisibleFlatColumns().length;
  const rows = table.getRowModel().rows;

  const isSortedBySystem = table.getState().sorting.some((sort) => sort.id === 'system');

  useEffect(() => {
    lastSortState.current = isSortedBySystem;
  }, [isSortedBySystem]);

  /**
   * Determines whether games should be grouped by system based on the current table state.
   * Groups are shown when all of the following are true:
   * 1. The table is sorted by system.
   * 2. Multiple systems are present on the current page.
   * 3. The table is not in a loading state.
   */
  const getShouldGroupBySystem = (): boolean => {
    if (isLoading && wasShowingGroups.current) {
      return true;
    }

    if (isLoading && isSortedBySystem && !lastSortState.current) {
      return false;
    }

    const hasMultipleSystems = rows?.length > 0 && new Set(rows.map(getSystemName)).size > 1;
    const shouldShow = !isLoading && isSortedBySystem && hasMultipleSystems;

    wasShowingGroups.current = shouldShow;

    return shouldShow;
  };
  const shouldGroupBySystem = getShouldGroupBySystem();

  /**
   * Builds the final array of table rows to be shown to the user in the UI.
   * This function also inserts system header rows when grouping is active.
   * When grouped, a header row is inserted before each group of games from the
   * same system.
   */
  const buildTableRows = (
    rows: Row<App.Platform.Data.GameListEntry>[],
  ): (Row<App.Platform.Data.GameListEntry> | SystemHeader)[] => {
    if (!shouldGroupBySystem) {
      return rows;
    }

    const result: Array<Row<App.Platform.Data.GameListEntry> | SystemHeader> = [];
    let currentSystem = '';

    for (const [index, row] of rows.entries()) {
      const systemName = getSystemName(row);

      // Insert a header row when transitioning to a new system.
      if (systemName !== currentSystem) {
        const groupSize = rows.slice(index).filter((r) => getSystemName(r) === systemName).length;

        result.push({
          type: 'header',
          system: systemName,
          count: groupSize,
        });
        currentSystem = systemName;
      }

      result.push(row);
    }

    return result;
  };

  return (
    <>
      {/* It's really detrimental to screen readers if we don't do this. */}
      <div aria-live="polite" className="sr-only">
        {shouldGroupBySystem
          ? t('Games are now grouped by system')
          : t('Games are no longer grouped')}
      </div>

      <BaseTable
        containerClassName={cn(
          'overflow-auto rounded-md border border-neutral-700/80 bg-embed',
          'light:border-neutral-300 lg:overflow-visible lg:rounded-sm',
          'transition-opacity duration-150',
          {
            'lg:!overflow-x-scroll': visibleColumnCount > 8,
            'xl:!overflow-x-scroll': visibleColumnCount > 10,
            'opacity-50': isLoading,
            'opacity-100': !isLoading,
            'system-sort-active': shouldGroupBySystem,
          },
        )}
      >
        <TableHeader table={table} visibleColumnCount={visibleColumnCount} />

        <BaseTableBody>
          {rows.length ? (
            buildTableRows(rows).map((row, index) =>
              isSystemHeader(row) ? (
                <SystemHeaderRow
                  key={`header-${index}`}
                  systemName={row.system}
                  gameCount={row.count}
                  columnCount={table.getAllColumns().length}
                />
              ) : (
                <GameRow key={row.id} row={row} shouldShowGroups={shouldGroupBySystem} />
              ),
            )
          ) : (
            <BaseTableRow>
              <BaseTableCell
                colSpan={table.getAllColumns().length}
                className="h-24 bg-embed text-center"
              >
                {t('No results.')}
              </BaseTableCell>
            </BaseTableRow>
          )}
        </BaseTableBody>
      </BaseTable>
    </>
  );
}

function getSystemName(row: Row<App.Platform.Data.GameListEntry>): string {
  return row.original.game.system?.name ?? '';
}

interface SystemHeader {
  /** Type guard discriminator. */
  type: 'header';
  /** Name of the system this header represents. */
  system: string;
  /** Number of games in this system group. */
  count: number;
}
function isSystemHeader(
  row: Row<App.Platform.Data.GameListEntry> | SystemHeader,
): row is SystemHeader {
  return 'type' in row && row.type === 'header';
}
