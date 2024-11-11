import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { DiffTimestamp } from '@/common/components/DiffTimestamp';
import { usePageProps } from '@/common/hooks/usePageProps';
import { buildTrackingClassNames } from '@/common/utils/buildTrackingClassNames';
import { cn } from '@/utils/cn';

import { HomeHeading } from '../../HomeHeading';
import { NewsCard } from './NewsCard';

export const FrontPageNews: FC = () => {
  const { recentNews } = usePageProps<App.Http.Data.HomePageProps>();

  const { t } = useTranslation();

  if (!recentNews) {
    return null;
  }

  return (
    <div className="flex flex-col gap-2.5">
      <HomeHeading className="sr-only">{t('News')}</HomeHeading>

      <div className="grid grid-cols-2 gap-5 sm:flex sm:flex-col sm:gap-1.5">
        {recentNews.map((news, index) => (
          <NewsCard
            key={`news-${news.id}`}
            authorDisplayName={news.user.displayName}
            href={news.link ?? undefined}
            imageSrc={news.image ?? undefined}
            PostedAt={<DiffTimestamp at={news.timestamp} enableTooltip={false} />}
            title={news.title}
            lead={news.payload}
            className={cn(
              index === 2 ? 'hidden sm:flex' : '',
              buildTrackingClassNames(`Click News Post ${index + 1}`),
            )}
          />
        ))}
      </div>

      {/* TODO add news archive */}
      {/* <SeeMoreLink href="#" asClientSideRoute={true} /> */}

      {/* TODO remove this once the SeeMoreLink is active */}
      <div className="mb-6" />
    </div>
  );
};
