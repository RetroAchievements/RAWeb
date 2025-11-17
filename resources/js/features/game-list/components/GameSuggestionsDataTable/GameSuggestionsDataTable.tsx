import type { Table } from '@tanstack/react-table';
import { getCoreRowModel, useReactTable } from '@tanstack/react-table';
import type { FC } from 'react';

import { usePageProps } from '@/common/hooks/usePageProps';

import { GameListDataTable } from '../GamesDataTableContainer/GameListDataTable';
import { useColumnDefinitions } from './useColumnDefinitions';

interface GameSuggestionsDataTableProps {
  showSourceGame?: boolean;
}

export const GameSuggestionsDataTable: FC<GameSuggestionsDataTableProps> = ({
  showSourceGame = true,
}) => {
  'use no memo'; // useReactTable does not support React Compiler

  const { paginatedGameListEntries } = usePageProps<App.Platform.Data.GameSuggestPageProps>();

  // eslint-disable-next-line react-hooks/incompatible-library -- https://github.com/TanStack/table/issues/5567
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
