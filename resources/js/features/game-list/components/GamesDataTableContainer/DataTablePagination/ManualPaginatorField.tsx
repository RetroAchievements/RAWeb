import type { Table } from '@tanstack/react-table';
import type { ChangeEvent, ReactNode } from 'react';
import { useEffect, useState } from 'react';
import { Trans, useTranslation } from 'react-i18next';
import { useDebounce } from 'react-use';

import { BaseInput } from '@/common/components/+vendor/BaseInput';
import { cn } from '@/common/utils/cn';

interface ManualPaginatorFieldProps<TData> {
  table: Table<TData>;
  onPageChange: (newPageIndex: number) => void;
}

export function ManualPaginatorField<TData>({
  table,
  onPageChange,
}: ManualPaginatorFieldProps<TData>): ReactNode {
  const { t } = useTranslation();

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
      {totalPages === 1 ? (
        <p>
          {t('Page {{currentPage, number}} of {{totalPages, number}}', {
            currentPage: 1,
            totalPages: 1,
          })}
        </p>
      ) : (
        <Trans
          i18nKey="Page <1></1> of {{totalPages, number}}"
          values={{ totalPages: table.getPageCount() }}
          components={{
            1: (
              <BaseInput
                type="number"
                min={1}
                max={totalPages}
                className={cn(
                  'h-8 max-w-[80px] pt-[5px] text-[13px] text-neutral-200 light:text-neutral-900',

                  // Hide the number spinner on desktop browsers -- it can obstruct the input field.
                  'appearance-none [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none',
                )}
                value={inputValue}
                onChange={(e: ChangeEvent<HTMLInputElement>) => setInputValue(e.target.value)}
                aria-label={t('current page number')}
              />
            ),
          }}
        />
      )}
    </div>
  );
}
