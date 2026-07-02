import React, { Dispatch, SetStateAction, useMemo, useRef, useState } from 'react';
import {DragDropProvider} from '@dnd-kit/react';
import {useSortable} from '@dnd-kit/react/sortable';
import {move} from '@dnd-kit/helpers';
import {
  flexRender,
  getCoreRowModel,
  useReactTable,
} from '@tanstack/react-table';
import type {
  ColumnDef,
  Header,
  Row as TableRow,
} from '@tanstack/react-table';
import UserAwardData = App.Community.Data.UserAwardData;

export type AwardType = 'mastery' | 'achievement_unlocks_yield' | 'achievement_points_yield' | 'patreon_supporter' | 'certified_legend' | 'game_beaten' | 'event' | 'playtest' | 'media_contribution';

const initialColumnOrder = ['badge', 'badgeSwitcher', 'title', 'dateAwarded', 'awardType', 'displayOrder'];

interface AwardTableProps {
  awards: UserAwardData[];
  setAwards: Dispatch<SetStateAction<UserAwardData[]>>;
  title: string;
}

export function AwardOrderTable({title, awards, setAwards}: AwardTableProps) {
  const [columnOrder, setColumnOrder] = useState<string[]>(initialColumnOrder);

  const initialOrder = useRef({
    columnOrder,
    data: awards,
  });

  const columns = useMemo<ColumnDef<UserAwardData>[]>(
    () => [
      {
        id: 'badge',
        header: 'Badge',
        // show the badge
        accessorKey: 'imageUrl',
        cell: ({ getValue, row }) => {
          const imageUrl = getValue<string | null>();

          if (!imageUrl) {
            return null;
          }

          return (
            <img
              src={imageUrl}
              alt={row.original.title ?? 'Award badge'}
              className={'h-8 w-8 object-contain ' + (row.original.isGold ? 'goldimage' : '')}
            />
          );
        },
      },
      {
        id: 'badgeSwitcher',
        header: 'Badge Switcher',
      },
      {
        id: 'title',
        accessorKey: 'title',
        header: 'Title',
      },
      {
        id: 'hidden',
        accessorKey: 'hidden',
        header: 'Date Awarded',
      },
      {
        id: 'awardType',
        accessorKey: 'awardType',
        header: 'Award Type',
      },
      {
        id: 'displayOrder',
        accessorKey: 'displayOrder',
        header: 'Order',
      },
    ],
    []
  );

  const table = useReactTable({
    data: awards,
    columns,
    state: {
      columnOrder,
    },
    onColumnOrderChange: setColumnOrder,
    getRowId: (row) => String(row.id),
    getCoreRowModel: getCoreRowModel(),
  });

  return (
    <>
      <div className="flex w-full items-center justify-between">
        <h4>{title}</h4>
        <select data-award-kind="game">
          <option value="1" selected>1</option>
        </select>
      </div>

      <DragDropProvider
        onDragStart={() => {
          initialOrder.current = {
            columnOrder,
            data: awards
          };
        }}
        onDragOver={(event) => {
          const { source } = event.operation;

          if (source?.type === 'column') {
            setColumnOrder((order) => move(order, event));
          } else {
            setAwards((rows) => {
              const newOrder = move(rows.map((r) => r.id), event);
              return newOrder.map((id) => rows.find((r) => String(r.id) === String(id))!);
            });
          }
        }}
        onDragEnd={(event) => {
          if (event.canceled) {
            setColumnOrder(initialOrder.current.columnOrder);
            setAwards(initialOrder.current.data);
          }
        }}
      >
        <div>
          <table>
            <thead>
            {table.getHeaderGroups().map((headerGroup) => (
              <tr key={headerGroup.id}>
                {headerGroup.headers.map((header, index) => (
                  <SortableHeader
                    key={header.id}
                    header={header}
                    index={index}
                  />
                ))}
              </tr>
            ))}
            </thead>
            <tbody>
            {table.getRowModel().rows.map((row, index) => (
              <SortableRow
                key={row.id}
                row={row}
                index={index}
                lastRow={index === table.getRowModel().rows.length - 1}
              />
            ))}
            </tbody>
          </table>
        </div>
      </DragDropProvider>
    </>
  );
}

interface SortableHeaderProps {
  header: Header<UserAwardData, unknown>;
  index: number;
}

function SortableHeader({ header, index }: SortableHeaderProps) {
  const {ref, isDragging} = useSortable({
    id: header.column.id,
    index,
    type: 'column',
    accept: 'column',
    modifiers: [/*RestrictToHorizontalAxis*/],
  });

  return (
    <th
      ref={ref}
    >
      {header.isPlaceholder
        ? null
        : flexRender(header.column.columnDef.header, header.getContext())}
    </th>
  );
}

interface SortableRowProps {
  row: TableRow<UserAwardData>;
  index: number;
  lastRow?: boolean;
}

function SortableRow({row, index, lastRow}: SortableRowProps) {
  const {ref, handleRef, isDragging} = useSortable({
    id: row.original.id,
    index,
    type: 'row',
    accept: 'row',
  });

  return (
    <tr
      ref={ref}
    >
      {row.getVisibleCells().map((cell) => (
        <td key={cell.id}>
          {flexRender(cell.column.columnDef.cell, cell.getContext())}
        </td>
      ))}
    </tr>
  );
}
