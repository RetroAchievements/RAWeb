import type { FC } from 'react';
import { useRef, useState } from 'react';
import UserAwardData = App.Community.Data.UserAwardData;
import { move } from '@dnd-kit/helpers';
import { DragDropProvider } from '@dnd-kit/react';
import { useSortable } from '@dnd-kit/react/sortable';
import { getCoreRowModel, useReactTable } from '@tanstack/react-table';

type Column = { id: string; name: string };

const columns: Column[] = [
  { id: 'imageUrl', name: 'Badge' },
  { id: 'title', name: 'Site Award' },
  { id: 'hidden', name: 'Hidden' },
  { id: 'manualMove', name: 'Manual Move' },
];
// https://tanstack.com/table/latest/docs/framework/react/examples/row-dnd?panel=sandbox
export const AwardOrderTable: FC<{ awards: UserAwardData[] }> = ({ awards }) => {
  'use no memo'; // useReactTable does not support React Compiler

  const [data, setData] = useState<UserAwardData[]>(awards);

  // eslint-disable-next-line react-hooks/incompatible-library -- https://github.com/TanStack/table/issues/5567
  const table = useReactTable({
    data,
    columns,
    getCoreRowModel: getCoreRowModel(),
  });

  const initialOrder = useRef({
    columns,
    data,
  });

  return (
    <DragDropProvider
      onDragStart={() => {
        initialOrder.current = {
          columns,
          data,
        };
      }}
      onDragOver={(event) => {
        const { source } = event.operation;

        setData((rows) => move(rows, event));
      }}
      onDragEnd={(event) => {
        if (event.canceled) {
          // setData(initialOrder.current.rows);
        }
      }}
    >
      <div
        style={{
          maxWidth: 800,
          marginInline: 'auto',
          overflow: 'hidden',
          borderRadius: 8,
          border: '1px solid #e2e8f0',
        }}
      >
        <table>
          <thead>
            <tr>
              <th />
              {columns.map((column, index) => (
                <th key={column.id}>{column.name}</th>
              ))}
            </tr>
          </thead>
          <tbody>
            {data.map((row, index) => (
              <SortableRow
                key={row.gameId}
                row={row}
                columns={columns}
                index={index}
                lastRow={index === data.length - 1}
              />
            ))}
          </tbody>
        </table>
      </div>
    </DragDropProvider>
  );
};

interface SortableRowProps {
  row: UserAwardData;
  columns: Column[];
  index: number;
  lastRow?: boolean;
}

function SortableRow({ row, columns, index, lastRow }: SortableRowProps) {
  const { ref, handleRef, isDragging } = useSortable({
    id: row.gameId,
    index,
    type: 'row',
    accept: 'row',
  });

  return (
    <tr
      ref={ref}
      style={{
        boxShadow: isDragging
          ? '0 0 0 1px rgba(63, 63, 68, 0.05), 0px 15px 15px 0 rgba(34, 33, 81, 0.25)'
          : undefined,
        opacity: isDragging ? 0.9 : undefined,
      }}
    >
      <td>True</td>
      {columns.map((column) => (
        <td key={column.id}>True</td>
      ))}
    </tr>
  );
}
