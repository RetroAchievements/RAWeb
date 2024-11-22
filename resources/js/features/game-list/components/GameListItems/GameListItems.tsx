import type { ColumnFiltersState, PaginationState, SortingState } from '@tanstack/react-table';
import { type FC, useEffect, useState } from 'react';
import { Fragment } from 'react';
import { useTranslation } from 'react-i18next';
import type { RouteName } from 'ziggy-js';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import { EmptyState } from '@/common/components/EmptyState';
import { usePageProps } from '@/common/hooks/usePageProps';

import { useGameListInfiniteQuery } from '../../hooks/useGameListInfiniteQuery';
import { GameListItemElement } from './GameListItemElement';
import { LoadingGameListItemContent } from './GameListItemElement/GameListItemContent/LoadingGameListItemContent';
import { InfiniteScroll } from './InfiniteScroll';

/**
 * 🔴 If you make layout updates to this component, you must
 *    also update <GameListItemsSuspenseFallback />'s layout.
 *    It's important that the loading skeleton always matches the real
 *    component's layout.
 */

interface GameListItemsProps {
  sorting: SortingState;
  pagination: PaginationState;
  columnFilters: ColumnFiltersState;

  apiRouteName?: RouteName;

  /**
   * If truthy, non-backlog items will be optimistically hidden from
   * the list. This is useful specifically for the user's Want to
   * Play Games List page, where we don't want to trigger a refetch,
   * but we do want to hide items when they're removed.
   */
  shouldHideItemIfNotInBacklog?: boolean;
}

const GameListItems: FC<GameListItemsProps> = ({
  columnFilters,
  pagination,
  sorting,
  apiRouteName = 'api.game.index',
  shouldHideItemIfNotInBacklog = false,
}) => {
  const { ziggy } = usePageProps();

  const { t } = useTranslation();

  const dataInfiniteQuery = useGameListInfiniteQuery({
    columnFilters,
    pagination,
    sorting,
    apiRouteName,
    isEnabled: ziggy.device === 'mobile',
  });

  const [visiblePageNumbers, setVisiblePageNumbers] = useState([1]);

  const [isLoadingMore, setIsLoadingMore] = useState(false);

  const isEmpty = dataInfiniteQuery.data?.pages?.[0].total === 0;

  /**
   * Because we prefetch data during scroll, even if we've loaded the last
   * page, it may not necessarily be visible to the user yet. We need to
   * keep track of this when determining whether or not to render the
   * "Load More" button.
   */
  const lastPageNumber = dataInfiniteQuery.data?.pages?.[0].lastPage;
  const isLastPageResultsVisible =
    visiblePageNumbers[visiblePageNumbers.length - 1] === lastPageNumber;

  const handleLoadMore = () => {
    if (dataInfiniteQuery.hasNextPage && !dataInfiniteQuery.isFetchingNextPage) {
      dataInfiniteQuery.fetchNextPage();
    }
  };

  const handleShowNextPageClick = () => {
    const nextPageNumber = visiblePageNumbers[visiblePageNumbers.length - 1] + 1;

    // Is the data available? If so, we can show the data to the user instantly.
    // Otherwise, we'll show a loading state. This will happen if the user has
    // a slow connection or the back-end is being exceptionally sluggish.
    const hasNextPageData = dataInfiniteQuery.data?.pages?.some(
      (page) => page.currentPage === nextPageNumber,
    );

    if (hasNextPageData) {
      setVisiblePageNumbers([...visiblePageNumbers, nextPageNumber]);
    } else {
      // We don't have the data yet. We need to wait for it.
      setIsLoadingMore(true);

      // Something may have gone wrong with the fetch, including the user just having
      // IntersectionObserver disabled for some reason. If we're not already fetching,
      // manually begin a fetch.
      if (!dataInfiniteQuery.isFetchingNextPage) {
        dataInfiniteQuery.fetchNextPage();
      }
    }
  };

  useEffect(() => {
    // If we're still waiting on data, as soon as it arrives we need to
    // synchronize our component state, otherwise we'll never be able to
    // leave the loading UI that's being shown to the user.
    if (isLoadingMore) {
      const nextPageNumber = visiblePageNumbers[visiblePageNumbers.length - 1] + 1;
      const hasNextPageData = dataInfiniteQuery.data?.pages?.some(
        (page) => page.currentPage === nextPageNumber,
      );

      if (hasNextPageData) {
        // The data finally arrived.
        setVisiblePageNumbers([...visiblePageNumbers, nextPageNumber]);
        setIsLoadingMore(false);
      }
    }
  }, [dataInfiniteQuery.data, isLoadingMore, visiblePageNumbers]);

  const visiblePages =
    dataInfiniteQuery.data?.pages?.filter((page) =>
      visiblePageNumbers.includes(page.currentPage),
    ) ?? [];

  return (
    <>
      {isEmpty ? (
        <EmptyState>
          {t('No games found. Try adjusting your search or filter criteria.')}
        </EmptyState>
      ) : null}

      <ol className="flex flex-col gap-2">
        {visiblePages.map((group, groupIdx) => (
          <Fragment key={groupIdx}>
            {group.items.map((item, itemIdx) => {
              const isLastItem =
                groupIdx === visiblePages.length - 1 && itemIdx === group.items.length - 1;

              return (
                <GameListItemElement
                  key={`mobile-${item.game.id}`}
                  gameListEntry={item}
                  sortFieldId={sorting?.[0]?.id as App.Platform.Enums.GameListSortField}
                  isLastItem={isLastItem}
                  shouldHideItemIfNotInBacklog={shouldHideItemIfNotInBacklog}
                />
              );
            })}
          </Fragment>
        ))}

        {isLoadingMore ? (
          <div>
            <p className="sr-only">{t('Loading...')}</p>

            {/* Render a fixed number of loading skeletons. */}
            {Array.from({ length: 24 }).map((_, index) => (
              <LoadingGameListItemContent key={`loading-${index}`} />
            ))}
          </div>
        ) : null}

        {dataInfiniteQuery.hasNextPage || !isLastPageResultsVisible ? (
          <div className="my-2" onClick={handleShowNextPageClick}>
            <BaseButton
              className="w-full hover:!border-neutral-700 hover:!bg-embed hover:!text-link"
              onClick={handleShowNextPageClick}
            >
              {t('Load more')}
            </BaseButton>
          </div>
        ) : null}

        <InfiniteScroll loadMore={handleLoadMore} />
      </ol>

      {!dataInfiniteQuery.hasNextPage &&
      !dataInfiniteQuery.isPending &&
      !isEmpty &&
      isLastPageResultsVisible ? (
        <p className="text-muted mt-4 p-4 text-center">
          {t("You've reached the end of the list.")}
        </p>
      ) : null}
    </>
  );
};

// Lazy-loaded, so using a default export.
export default GameListItems;
