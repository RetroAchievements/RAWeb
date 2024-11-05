import { useLaravelReactI18n } from 'laravel-react-i18n';
import type { FC } from 'react';
import { LuMenu } from 'react-icons/lu';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import { EmptyState } from '@/common/components/EmptyState';
import { useFormatNumber } from '@/common/hooks/useFormatNumber';

import { HomeHeading } from '../../HomeHeading';

export const ActivePlayers: FC = () => {
  const { t } = useLaravelReactI18n();

  const { formatNumber } = useFormatNumber();

  return (
    <div>
      <HomeHeading>{t('Active Players')}</HomeHeading>

      <div className="mb-1 flex w-full items-center justify-between">
        <p>
          {t('Viewing')} <span className="font-bold">{formatNumber(0)}</span>{' '}
          {t('players in-game.')}
        </p>

        <BaseButton size="sm">
          <LuMenu className="h-4 w-4" />
          <span className="sr-only">{t('Open active players menu')}</span>
        </BaseButton>
      </div>

      <div className="h-[325px] w-full rounded bg-embed py-8">
        <EmptyState>{t("Couldn't find any active players.")}</EmptyState>
      </div>

      <div className="flex w-full justify-end">
        <p className="text-2xs">{t('Last updated at :timestamp', { timestamp: 'TODO' })}</p>
      </div>
    </div>
  );
};
