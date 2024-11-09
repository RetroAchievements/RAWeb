import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuArrowLeft, LuArrowLeftToLine, LuArrowRight, LuArrowRightToLine } from 'react-icons/lu';

import {
  BasePagination,
  BasePaginationContent,
  BasePaginationItem,
  BasePaginationLink,
} from '@/common/components/+vendor/BasePagination';
import { cn } from '@/utils/cn';

import { baseButtonVariants } from '../+vendor/BaseButton';
import {
  BaseSelect,
  BaseSelectContent,
  BaseSelectItem,
  BaseSelectTrigger,
  BaseSelectValue,
} from '../+vendor/BaseSelect';

interface FullPaginatorProps<TData = unknown> {
  onPageSelectValueChange: (newPageValue: number) => void;
  paginatedData: App.Data.PaginatedData<TData>;
}

export const FullPaginator: FC<FullPaginatorProps> = ({
  onPageSelectValueChange,
  paginatedData,
}) => {
  const { t } = useTranslation();

  const {
    currentPage,
    lastPage,
    links: { firstPageUrl, lastPageUrl, nextPageUrl, previousPageUrl },
  } = paginatedData;

  if (!previousPageUrl && !nextPageUrl) {
    return <span />;
  }

  // Generate an array of all page numbers. We'll use this to populate the select control.
  const pageOptions = Array.from({ length: lastPage }, (_, i) => i + 1);

  return (
    <BasePagination>
      <BasePaginationContent>
        {currentPage !== 1 && firstPageUrl ? (
          <BasePaginationItem aria-label={t('Go to first page')}>
            <BasePaginationLink
              href={firstPageUrl}
              className={baseButtonVariants({
                variant: 'default',
                className: 'h-8 w-8 cursor-pointer p-0',
              })}
            >
              <LuArrowLeftToLine className="h-4 w-4" />
            </BasePaginationLink>
          </BasePaginationItem>
        ) : null}

        {currentPage !== 1 && previousPageUrl ? (
          <BasePaginationItem aria-label={t('Go to previous page')}>
            <BasePaginationLink
              href={previousPageUrl}
              className={baseButtonVariants({
                variant: 'default',
                className: 'h-8 w-8 cursor-pointer p-0',
              })}
            >
              <LuArrowLeft className="h-4 w-4" />
            </BasePaginationLink>
          </BasePaginationItem>
        ) : null}

        <BasePaginationItem
          className={cn(
            'flex items-center gap-2',
            currentPage !== 1 ? 'ml-2' : null,
            currentPage !== lastPage ? 'mr-2' : null,
          )}
        >
          <span>{t('Page')}</span>

          <BaseSelect
            value={String(currentPage)}
            onValueChange={(value) => onPageSelectValueChange(Number(value))}
          >
            <BaseSelectTrigger className="!h-[32px] min-w-[70px]">
              <BaseSelectValue />
            </BaseSelectTrigger>

            <BaseSelectContent>
              {pageOptions.map((page) => (
                <BaseSelectItem key={`page-value-${page}`} value={page.toString()}>
                  {page}
                </BaseSelectItem>
              ))}
            </BaseSelectContent>
          </BaseSelect>

          <span className="whitespace-nowrap">
            {t('of {{pageNumber, number}}', { pageNumber: lastPage })}
          </span>
        </BasePaginationItem>

        {currentPage !== lastPage && nextPageUrl ? (
          <BasePaginationItem aria-label={t('Go to next page')}>
            <BasePaginationLink
              href={nextPageUrl}
              className={baseButtonVariants({
                variant: 'default',
                className: 'h-8 w-8 cursor-pointer p-0',
              })}
            >
              <LuArrowRight className="h-4 w-4" />
            </BasePaginationLink>
          </BasePaginationItem>
        ) : null}

        {currentPage !== lastPage && lastPageUrl ? (
          <BasePaginationItem aria-label={t('Go to last page')}>
            <BasePaginationLink
              href={lastPageUrl}
              className={baseButtonVariants({
                variant: 'default',
                className: 'h-8 w-8 cursor-pointer p-0',
              })}
            >
              <LuArrowRightToLine className="h-4 w-4" />
            </BasePaginationLink>
          </BasePaginationItem>
        ) : null}
      </BasePaginationContent>
    </BasePagination>
  );
};
