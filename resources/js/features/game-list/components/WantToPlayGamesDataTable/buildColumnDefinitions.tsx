import type { ColumnDef } from '@tanstack/react-table';
import dayjs from 'dayjs';
import localizedFormat from 'dayjs/plugin/localizedFormat';
import utc from 'dayjs/plugin/utc';

import { GameAvatar } from '@/common/components/GameAvatar';
import { PlayerGameProgressBar } from '@/common/components/PlayerGameProgressBar';
import { SystemChip } from '@/common/components/SystemChip/SystemChip';
import { WeightedPointsContainer } from '@/common/components/WeightedPointsContainer';
import { formatDate } from '@/common/utils/l10n/formatDate';
import { formatNumber } from '@/common/utils/l10n/formatNumber';

import { DataTableColumnHeader } from './DataTableColumnHeader';
import { DataTableRowActions } from './DataTableRowActions';

dayjs.extend(utc);
dayjs.extend(localizedFormat);

export function buildColumnDefinitions(options: {
  canSeeOpenTicketsColumn: boolean;
  forUsername?: string;
}): ColumnDef<App.Platform.Data.GameListEntry>[] {
  const columnDefinitions: ColumnDef<App.Platform.Data.GameListEntry>[] = [
    {
      id: 'title',
      accessorKey: 'game',
      meta: { label: 'Title' },
      enableHiding: false,
      header: ({ column, table }) => <DataTableColumnHeader column={column} table={table} />,
      cell: ({ row }) => {
        if (!row.original.game) {
          return null;
        }

        return (
          <div className="max-w-fit">
            <div className="max-w-[400px]">
              <GameAvatar
                {...row.original.game}
                size={32}
                showHoverCardProgressForUsername={options.forUsername}
              />
            </div>
          </div>
        );
      },
    },

    {
      id: 'system',
      accessorKey: 'game',
      meta: { label: 'System' },
      header: ({ column, table }) => <DataTableColumnHeader column={column} table={table} />,
      cell: ({ row }) => {
        if (!row.original.game?.system) {
          return null;
        }

        return <SystemChip {...row.original.game.system} />;
      },
    },

    {
      id: 'achievementsPublished',
      accessorKey: 'game',
      meta: { label: 'Achievements', align: 'right' },
      header: ({ column, table }) => (
        <DataTableColumnHeader column={column} table={table} sortType="quantity" />
      ),
      cell: ({ row }) => {
        const achievementsPublished = row.original.game?.achievementsPublished ?? 0;

        return (
          <p className={achievementsPublished === 0 ? 'text-muted' : ''}>{achievementsPublished}</p>
        );
      },
    },

    {
      id: 'pointsTotal',
      accessorKey: 'game',
      meta: { label: 'Points', align: 'right' },
      header: ({ column, table }) => (
        <DataTableColumnHeader column={column} table={table} sortType="quantity" />
      ),
      cell: ({ row }) => {
        const pointsTotal = row.original.game?.pointsTotal ?? 0;
        const pointsWeighted = row.original.game?.pointsWeighted ?? 0;

        if (pointsTotal === 0) {
          return <p className="text-muted">{pointsTotal}</p>;
        }

        return (
          <p className="whitespace-nowrap">
            {formatNumber(pointsTotal)}{' '}
            <WeightedPointsContainer>({formatNumber(pointsWeighted)})</WeightedPointsContainer>
          </p>
        );
      },
    },

    {
      id: 'retroRatio',
      accessorKey: 'game',
      meta: { label: 'Rarity', align: 'right' },
      header: ({ column, table }) => (
        <DataTableColumnHeader column={column} table={table} sortType="quantity" />
      ),
      cell: ({ row }) => {
        const pointsTotal = row.original.game?.pointsTotal ?? 0;

        if (pointsTotal === 0) {
          return <p className="text-muted italic">none</p>;
        }

        const pointsWeighted = row.original.game?.pointsWeighted ?? 0;

        const result = pointsWeighted / pointsTotal;

        return <p>&times;{(Math.round((result + Number.EPSILON) * 100) / 100).toFixed(2)}</p>;
      },
    },

    {
      id: 'lastUpdated',
      accessorKey: 'game',
      meta: { label: 'Last Updated' },
      header: ({ column, table }) => (
        <DataTableColumnHeader column={column} table={table} sortType="date" />
      ),
      cell: ({ row }) => {
        const date = row.original.game?.lastUpdated ?? new Date();

        return <p>{formatDate(dayjs.utc(date), 'll')}</p>;
      },
    },

    {
      id: 'releasedAt',
      accessorKey: 'game',
      meta: { label: 'Release Date' },
      header: ({ column, table }) => (
        <DataTableColumnHeader column={column} table={table} sortType="date" />
      ),
      cell: ({ row }) => {
        const date = row.original.game?.releasedAt ?? null;
        const granularity = row.original.game?.releasedAtGranularity ?? 'day';

        if (!date) {
          return <p className="text-muted italic">unknown</p>;
        }

        const dayjsDate = dayjs.utc(date);
        let formattedDate;
        if (granularity === 'day') {
          formattedDate = formatDate(dayjsDate, 'll');
        } else if (granularity === 'month') {
          formattedDate = dayjsDate.format('MMM YYYY');
        } else {
          formattedDate = dayjsDate.format('YYYY');
        }

        return <p>{formattedDate}</p>;
      },
    },

    {
      id: 'playersTotal',
      accessorKey: 'game',
      meta: { label: 'Players', align: 'right' },
      header: ({ column, table }) => (
        <DataTableColumnHeader column={column} table={table} sortType="quantity" />
      ),
      cell: ({ row }) => {
        const playersTotal = row.original.game?.playersTotal ?? 0;

        return (
          <p className={playersTotal === 0 ? 'text-muted' : ''}>{formatNumber(playersTotal)}</p>
        );
      },
    },

    {
      id: 'numVisibleLeaderboards',
      accessorKey: 'game',
      meta: { label: 'Leaderboards', align: 'right' },
      header: ({ column, table }) => (
        <DataTableColumnHeader column={column} table={table} sortType="quantity" />
      ),
      cell: ({ row }) => {
        const numVisibleLeaderboards = row.original.game?.numVisibleLeaderboards ?? 0;

        return (
          <p className={numVisibleLeaderboards === 0 ? 'text-muted' : ''}>
            {numVisibleLeaderboards}
          </p>
        );
      },
    },
  ];

  if (options.canSeeOpenTicketsColumn) {
    columnDefinitions.push({
      id: 'numUnresolvedTickets',
      accessorKey: 'game',
      meta: { label: 'Tickets', align: 'right' },
      header: ({ column, table }) => (
        <DataTableColumnHeader column={column} table={table} sortType="quantity" />
      ),
      cell: ({ row }) => {
        const numUnresolvedTickets = row.original.game?.numUnresolvedTickets ?? 0;
        const gameId = row.original.game?.id ?? 0;

        return (
          <a
            href={route('game.tickets', { game: gameId, 'filter[achievement]': 'core' })}
            className={numUnresolvedTickets === 0 ? 'text-muted' : ''}
          >
            {numUnresolvedTickets}
          </a>
        );
      },
    });
  }

  columnDefinitions.push(
    ...([
      {
        id: 'progress',
        accessorKey: 'game',
        meta: { label: 'Progress', align: 'left' },
        header: ({ column, table }) => (
          <DataTableColumnHeader column={column} table={table} sortType="quantity" />
        ),
        cell: ({ row }) => {
          const { game, playerGame } = row.original;

          return <PlayerGameProgressBar game={game} playerGame={playerGame} />;
        },
      },

      {
        id: 'actions',
        cell: ({ row }) => <DataTableRowActions row={row} />,
      },
    ] satisfies ColumnDef<App.Platform.Data.GameListEntry>[]),
  );

  return columnDefinitions;
}
