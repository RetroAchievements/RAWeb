import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuChevronFirst, LuChevronLast, LuChevronLeft, LuChevronRight } from 'react-icons/lu';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import { BasePagination, BasePaginationContent } from '@/common/components/+vendor/BasePagination';
import { cn } from '@/common/utils/cn';

import { ManualPaginatorField } from './ManualPaginatorField';

interface DataTablePaginationControlsProps {
  currentPage: number;
  lastPage: number;
  onPageChange: (pageNumber: number) => void;

  onPrefetchPage?: (pageNumber: number) => void;
}

export const DataTablePaginationControls: FC<DataTablePaginationControlsProps> = ({
  currentPage,
  lastPage,
  onPageChange,
  onPrefetchPage,
}) => {
  const { t } = useTranslation();

  const goToPage = (pageNumber: number, prefetchDirection?: -1 | 1) => {
    onPageChange(pageNumber);

    if (prefetchDirection) {
      const adjacentPage = pageNumber + prefetchDirection;
      if (adjacentPage >= 1 && adjacentPage <= lastPage) {
        onPrefetchPage?.(adjacentPage);
      }
    }

    scrollToPaginationScrollTarget();
  };

  const isOnFirstPage = currentPage === 1;
  const isOnLastPage = currentPage === lastPage;

  const buttonClassNames = cn(
    'border-none hover:outline-1 hover:outline-neutral-300 hover:light:outline-neutral-200',
    'aria-disabled:pointer-events-none aria-disabled:opacity-50',
  );

  return (
    <BasePagination className="flex items-center gap-6 lg:gap-8">
      <BasePaginationContent className="flex items-center gap-2" role="group">
        <BaseButton
          size="sm"
          className={buttonClassNames}
          onClick={() => goToPage(1, 1)}
          onMouseEnter={() => onPrefetchPage?.(1)}
          disabled={isOnFirstPage}
          aria-label={t('Go to first page')}
        >
          <LuChevronFirst className="size-4" aria-hidden={true} />
        </BaseButton>

        <BaseButton
          size="sm"
          className={buttonClassNames}
          onClick={() => goToPage(currentPage - 1, -1)}
          onMouseEnter={() => onPrefetchPage?.(currentPage - 1)}
          disabled={isOnFirstPage}
          aria-label={t('Go to previous page')}
        >
          <LuChevronLeft className="size-4" aria-hidden={true} />
        </BaseButton>

        <ManualPaginatorField
          currentPage={currentPage}
          totalPages={lastPage}
          onPageChange={goToPage}
        />

        <BaseButton
          size="sm"
          className={buttonClassNames}
          onClick={() => goToPage(currentPage + 1, 1)}
          onMouseEnter={() => onPrefetchPage?.(currentPage + 1)}
          disabled={isOnLastPage}
          aria-label={t('Go to next page')}
        >
          <LuChevronRight className="size-4" aria-hidden={true} />
        </BaseButton>

        <BaseButton
          size="sm"
          className={buttonClassNames}
          onClick={() => goToPage(lastPage, -1)}
          onMouseEnter={() => onPrefetchPage?.(lastPage)}
          disabled={isOnLastPage}
          aria-label={t('Go to last page')}
        >
          <LuChevronLast className="size-4" aria-hidden={true} />
        </BaseButton>
      </BasePaginationContent>
    </BasePagination>
  );
};

/**
 * We use a `setTimeout()` without any time here to deliberately
 * push the scroll event to the end of the browser's event queue.
 * If we don't do this, scroll events for navigating to the first
 * and last page may not occur on some browsers.
 */
export function scrollToPaginationScrollTarget(): void {
  setTimeout(() => {
    const scrollTarget = document.getElementById('pagination-scroll-target');

    if (!scrollTarget) {
      return;
    }

    window.scrollTo({
      top: scrollTarget.offsetTop,
      behavior: 'smooth',
    });
  });
}
