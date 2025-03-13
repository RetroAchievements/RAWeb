import { AnimatePresence } from 'motion/react';
import * as motion from 'motion/react-m';
import { type FC, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { LuArrowLeft, LuArrowRight } from 'react-icons/lu';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import { BasePagination, BasePaginationContent } from '@/common/components/+vendor/BasePagination';
import { usePageProps } from '@/common/hooks/usePageProps';
import { buildTrackingClassNames } from '@/common/utils/buildTrackingClassNames';
import { cn } from '@/common/utils/cn';

import { NewsCard } from '../NewsCard';

export const DesktopNews: FC = () => {
  const { recentNews } = usePageProps<App.Http.Data.HomePageProps>();
  const { t } = useTranslation();
  const [currentPage, setCurrentPage] = useState(1);

  if (!recentNews?.length) {
    return null;
  }

  const pageSize = 3;
  const totalPages = Math.ceil(recentNews.length / pageSize);
  const startIndex = (currentPage - 1) * pageSize;
  const endIndex = startIndex + pageSize;
  const currentPageNewsItems = recentNews.slice(startIndex, endIndex);

  const handleGoToPreviousPageClick = () => {
    setCurrentPage((prev) => prev - 1);
  };

  const handleGoToNextPageClick = () => {
    setCurrentPage((prev) => prev + 1);
  };

  const isFirstPage = currentPage === 1;
  const isLastPage = currentPage === totalPages;

  return (
    <>
      <div
        className="grid grid-cols-2 gap-5 sm:flex sm:flex-col sm:gap-1.5"
        data-testid="desktop-news"
      >
        <AnimatePresence mode="wait" initial={false}>
          <motion.div key={currentPage} className="contents">
            {currentPageNewsItems.map((news, index) => (
              <motion.div
                key={`news-${news.id}`}
                initial={{ opacity: 0, y: 6 }}
                animate={{ opacity: 1, y: 0 }}
                exit={{ opacity: 0, y: -6 }}
                transition={{
                  duration: 0.15,
                  delay: index * 0.025,
                  ease: [0.25, 0.1, 0.25, 1.0],
                }}
              >
                <NewsCard
                  news={news}
                  className={cn(
                    index === 2 ? 'hidden sm:flex' : '',
                    buildTrackingClassNames(`Click News Post ${index + 1}`),
                  )}
                />
              </motion.div>
            ))}
          </motion.div>
        </AnimatePresence>
      </div>

      <BasePagination className="mb-4">
        <BasePaginationContent className="flex items-center justify-end gap-2" role="group">
          <BaseButton
            className="size-8 p-0"
            aria-label={t('Go to previous news page')}
            onClick={handleGoToPreviousPageClick}
            disabled={isFirstPage}
          >
            <LuArrowLeft className="size-4" aria-hidden={true} />
          </BaseButton>

          <BaseButton
            className="size-8 p-0"
            aria-label={t('Go to next news page')}
            onClick={handleGoToNextPageClick}
            disabled={isLastPage}
          >
            <LuArrowRight className="size-4" aria-hidden={true} />
          </BaseButton>
        </BasePaginationContent>
      </BasePagination>
    </>
  );
};
