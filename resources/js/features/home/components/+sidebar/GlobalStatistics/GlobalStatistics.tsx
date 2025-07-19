import dayjs from 'dayjs';
import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { route } from 'ziggy-js';

import { useFormatNumber } from '@/common/hooks/useFormatNumber';
import { usePageProps } from '@/common/hooks/usePageProps';
import { buildTrackingClassNames } from '@/common/utils/buildTrackingClassNames';
import { formatDate } from '@/common/utils/l10n/formatDate';

import { HomeHeading } from '../../HomeHeading';
import { StatBox } from './StatBox';

export const GlobalStatistics: FC = () => {
  const { staticData } = usePageProps<App.Http.Data.HomePageProps>();

  const { t } = useTranslation();

  const { formatNumber } = useFormatNumber();

  return (
    <div>
      <HomeHeading>{t('Statistics')}</HomeHeading>

      <div className="grid gap-1.5">
        <div className="grid grid-cols-2 gap-1.5 sm:grid-cols-3 lg:grid-cols-2">
          <StatBox
            t_label={t('Games')}
            href={route('game.index')}
            asClientSideRoute={true}
            anchorClassName={buildTrackingClassNames('Click Statistic Games')}
          >
            {formatNumber(staticData?.numGames)}
          </StatBox>

          <StatBox
            t_label={t('Achievements')}
            href="/achievementList.php"
            anchorClassName={buildTrackingClassNames('Click Statistic Achievements')}
          >
            {formatNumber(staticData?.numAchievements)}
          </StatBox>

          <StatBox
            t_label={t('Sets Mastered')}
            href="/recentMastery.php?t=1&m=1"
            anchorClassName={buildTrackingClassNames('Click Statistic Games Mastered')}
          >
            {formatNumber(staticData?.numHardcoreMasteryAwards)}
          </StatBox>

          <StatBox
            t_label={t('Games Beaten')}
            href="/recentMastery.php?t=8&m=1"
            anchorClassName={buildTrackingClassNames('Click Statistic Games Beaten')}
          >
            {formatNumber(staticData?.numHardcoreGameBeatenAwards)}
          </StatBox>

          <StatBox
            t_label={t('Registered Players')}
            href="/userList.php"
            anchorClassName={buildTrackingClassNames('Click Statistic Registered Players')}
          >
            {formatNumber(staticData?.numRegisteredUsers)}
          </StatBox>

          <StatBox
            t_label={t('Achievement Unlocks')}
            href="/recentMastery.php"
            anchorClassName={buildTrackingClassNames('Click Statistic Achievement Unlocks')}
          >
            {formatNumber(staticData?.numAwarded)}
          </StatBox>
        </div>

        <div className="group flex h-full flex-col rounded bg-embed px-2 py-2.5">
          <p className="text-xs leading-4 text-neutral-400/90 light:text-neutral-950 lg:text-2xs">
            {t('Points Earned Since {{date}}', { date: formatDate(dayjs('2013-03-02'), 'LL') })}
          </p>
          <p className="!text-[20px] leading-7 text-neutral-300 light:text-neutral-950">
            {formatNumber(staticData?.totalPointsEarned)}
          </p>
        </div>
      </div>
    </div>
  );
};
