import { type FC } from 'react';
import { useTranslation } from 'react-i18next';

import { usePageProps } from '@/common/hooks/usePageProps';

import { HomeHeading } from '../../HomeHeading';
import { DesktopNews } from './DesktopNews';
import { MobileNews } from './MobileNews';

export const FrontPageNews: FC = () => {
  const { recentNews, ziggy } = usePageProps<App.Http.Data.HomePageProps>();

  const { t } = useTranslation();

  if (!recentNews) {
    return null;
  }

  return (
    <div className="flex flex-col gap-2.5">
      <HomeHeading className="sr-only">{t('News')}</HomeHeading>

      {ziggy.device === 'mobile' ? <MobileNews /> : null}
      {ziggy.device === 'desktop' ? <DesktopNews /> : null}
    </div>
  );
};
