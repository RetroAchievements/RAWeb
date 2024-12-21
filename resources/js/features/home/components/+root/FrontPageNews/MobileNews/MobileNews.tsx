import { type FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuArrowRight } from 'react-icons/lu';

import { usePageProps } from '@/common/hooks/usePageProps';
import { buildTrackingClassNames } from '@/common/utils/buildTrackingClassNames';
import { cn } from '@/common/utils/cn';

import { NewsCard } from '../NewsCard';

export const MobileNews: FC = () => {
  const { recentNews } = usePageProps<App.Http.Data.HomePageProps>();

  const { t } = useTranslation();

  if (!recentNews?.length) {
    return null;
  }

  return (
    <div className="-mx-2.5 mb-5 flex flex-col gap-2" data-testid="mobile-news">
      <div className="flex snap-x snap-mandatory gap-x-5 overflow-scroll pl-2">
        {recentNews.map((news, index) => (
          <NewsCard
            key={`news-${news.id}`}
            news={news}
            className={cn(
              'w-[200px] min-w-[200px] snap-start pl-2',
              buildTrackingClassNames(`Click News Post ${index + 1}`),
            )}
          />
        ))}
      </div>

      <p className="-ml-2.5 flex items-center justify-center gap-1 text-center">
        {t('Swipe to view more')}
        <LuArrowRight />
      </p>
    </div>
  );
};
