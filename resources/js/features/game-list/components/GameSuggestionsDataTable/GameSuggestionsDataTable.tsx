import type { Table } from '@tanstack/react-table';
import { getCoreRowModel, useReactTable } from '@tanstack/react-table';
import type { FC } from 'react';

import { usePageProps } from '@/common/hooks/usePageProps';

import { GameListDataTable } from '../GameListDataTable';
import { useColumnDefinitions } from './useColumnDefinitions';

interface GameSuggestionsDataTableProps {
  showSourceGame?: boolean;
}

export const GameSuggestionsDataTable: FC<GameSuggestionsDataTableProps> = ({
  showSourceGame = true,
}) => {
  const { paginatedGameListEntries } = usePageProps<App.Platform.Data.GameSuggestPageProps>();

  const table = useReactTable({
    columns: useColumnDefinitions({ showSourceGame }),
    data: paginatedGameListEntries.items,

    getCoreRowModel: getCoreRowModel(),
  });

  return (
    <div className="flex flex-col gap-3">
      <GameListDataTable table={table as unknown as Table<App.Platform.Data.GameListEntry>} />
    </div>
  );
};
