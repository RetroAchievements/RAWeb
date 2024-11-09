import { Link } from '@inertiajs/react';
import dayjs from 'dayjs';
import type { FC, ReactNode } from 'react';
import { useTranslation } from 'react-i18next';

import { useFormatNumber } from '@/common/hooks/useFormatNumber';
import { formatDate } from '@/common/utils/l10n/formatDate';

import { HomeHeading } from '../../HomeHeading';

// TODO tracking

export const GlobalStatistics: FC = () => {
  const { t } = useTranslation();

  const { formatNumber } = useFormatNumber();

  return (
    <div>
      <HomeHeading>{t('Statistics')}</HomeHeading>

      <div className="flex flex-col gap-1.5">
        <div className="grid grid-cols-2 gap-1.5 sm:grid-cols-3 lg:grid-cols-2">
          <StatBox t_label={t('Games')} href={route('game.index')} asClientSideRoute={true}>
            {formatNumber(8328)}
          </StatBox>

          <StatBox t_label={t('Achievements')} href="/achievementList.php">
            {formatNumber(449508)}
          </StatBox>

          <StatBox t_label={t('Games Mastered')} href="/recentMastery.php?t=1&m=1">
            {formatNumber(378343)}
          </StatBox>

          <StatBox t_label={t('Games Beaten')} href="/recentMastery.php?t=8&m=1">
            {formatNumber(646049)}
          </StatBox>

          <StatBox t_label={t('Registered Players')} href="/userList.php">
            {formatNumber(799254)}
          </StatBox>

          <StatBox t_label={t('Achievement Unlocks')} href="/recentMastery.php">
            {formatNumber(67731865)}
          </StatBox>
        </div>

        <div className="group flex h-full flex-col rounded bg-embed px-2 py-2.5">
          <p className="text-xs leading-4 text-neutral-400/90 light:text-neutral-950 lg:text-2xs">
            {t('Points Earned Since {{date}}', { date: formatDate(dayjs('2013-03-02'), 'LL') })}
          </p>
          <p className="!text-[20px] leading-7 text-neutral-300 light:text-neutral-950">
            {formatNumber(575649825)}
          </p>
        </div>
      </div>
    </div>
  );
};

interface StatBoxProps {
  t_label: string;
  href: string;
  children: ReactNode;

  /**
   * If the destination page is also a React page, we should client-side
   * route to improve the performance of loading that page.
   */
  asClientSideRoute?: boolean;
}

const StatBox: FC<StatBoxProps> = ({ t_label, href, children, asClientSideRoute = false }) => {
  const Wrapper = asClientSideRoute ? Link : 'a';
  const labelId = `${t_label.toLowerCase().replace(/\s+/g, '-')}-label`;

  return (
    <Wrapper
      href={href}
      className="group flex h-full flex-col rounded border border-neutral-700/80 bg-embed px-2 py-2.5 hover:border-neutral-50"
    >
      <span
        id={labelId}
        className="text-xs leading-4 text-neutral-400/90 group-hover:text-neutral-50 light:text-neutral-950 lg:text-2xs"
      >
        {t_label}
      </span>

      <p
        aria-labelledby={labelId}
        className="!text-[20px] leading-7 text-neutral-300 group-hover:text-neutral-50 light:text-neutral-950"
      >
        {children}
      </p>
    </Wrapper>
  );
};
