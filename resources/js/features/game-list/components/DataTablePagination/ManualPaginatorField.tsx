import type { Table } from '@tanstack/react-table';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import type { ChangeEvent, ReactNode } from 'react';
import { useEffect, useState } from 'react';
import { useDebounce } from 'react-use';

import { BaseInput } from '@/common/components/+vendor/BaseInput';
import { useFormatNumber } from '@/common/hooks/useFormatNumber';

interface ManualPaginatorFieldProps<TData> {
  table: Table<TData>;
  onPageChange: (newPageIndex: number) => void;
}

export function ManualPaginatorField<TData>({
  table,
  onPageChange,
}: ManualPaginatorFieldProps<TData>): ReactNode {
  const { t } = useLaravelReactI18n();

  const { formatNumber } = useFormatNumber();

  const { pagination } = table.getState();

  const currentPage = pagination.pageIndex + 1;
  const totalPages = table.getPageCount();

  const [inputValue, setInputValue] = useState(String(currentPage));

  // Sync the input field with table state for when
  // pagination changes externally (ie: the pagination buttons).
  useEffect(() => {
    setInputValue(String(currentPage));
  }, [currentPage]);

  useDebounce(
    () => {
      const newPage = Number(inputValue);
      if (newPage >= 1 && newPage <= totalPages && newPage !== currentPage) {
        onPageChange(newPage - 1);
      }
    },
    800,
    [inputValue],
  );

  return (
    <div className="flex items-center gap-2 whitespace-nowrap text-neutral-200 light:text-neutral-900">
      {/* The Trans component doesn't work here. BaseInput is too complex of a child. */}
      {t('Page')}{' '}
      <BaseInput
        type="number"
        min={1}
        max={totalPages}
        className="h-8 max-w-[80px] pt-[5px] text-[13px] text-neutral-200 light:text-neutral-900"
        value={inputValue}
        onChange={(e: ChangeEvent<HTMLInputElement>) => setInputValue(e.target.value)}
        aria-label={t('current page number')}
      />{' '}
      {t('of :totalPages', { totalPages: formatNumber(table.getPageCount()) })}
    </div>
  );
}
