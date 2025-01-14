import type { ColumnDef } from '@tanstack/react-table';

import type { TranslatedString } from '@/types/i18next';

import { DataTableColumnHeader } from '../../components/DataTableColumnHeader';
import { SuggestionReasonCell } from '../../components/SuggestionReasonCell';
import { gameListFieldIconMap } from '../gameListFieldIconMap';

interface BuildSuggestionReasonColumnDefProps {
  sourceGame: App.Platform.Data.Game | null;
  t_label: TranslatedString;
}

export function buildSuggestionReasonColumnDef({
  sourceGame,
  t_label,
}: BuildSuggestionReasonColumnDefProps): ColumnDef<App.Platform.Data.GameSuggestionEntry> {
  return {
    id: 'suggestionReason',
    accessorKey: 'suggestionReason',
    meta: { t_label, Icon: gameListFieldIconMap.suggestionReason },
    enableSorting: false,

    header: ({ column, table }) => <DataTableColumnHeader column={column} table={table} />,

    cell: ({ row }) => <SuggestionReasonCell originalRow={row.original} sourceGame={sourceGame} />,
  };
}
