import type { FC } from 'react';
import { Trans, useTranslation } from 'react-i18next';
import { LuCircleAlert } from 'react-icons/lu';

import {
  BaseAlert,
  BaseAlertDescription,
  BaseAlertTitle,
} from '@/common/components/+vendor/BaseAlert';

interface BeatenCreditAlertProps {
  hasProgressionAchievements: boolean;
  hasWinConditionAchievements: boolean;
}

export const BeatenCreditAlert: FC<BeatenCreditAlertProps> = ({
  hasProgressionAchievements,
  hasWinConditionAchievements,
}) => {
  const { t } = useTranslation();

  return (
    <BaseAlert>
      <LuCircleAlert className="size-5" />

      <BaseAlertTitle>{t('How to earn beaten credit:', { nsSeparator: null })}</BaseAlertTitle>
      <BaseAlertDescription>
        {hasProgressionAchievements && hasWinConditionAchievements ? (
          <Trans
            i18nKey="Unlock <1>ALL</1> progression achievements and <2>ANY</2> win condition achievement."
            components={{
              1: <span className="font-semibold text-green-500" />,
              2: <span className="font-semibold text-amber-500" />,
            }}
          />
        ) : null}

        {hasProgressionAchievements && !hasWinConditionAchievements ? (
          <Trans
            i18nKey="Unlock <1>ALL</1> progression achievements."
            components={{
              1: <span className="font-semibold text-green-500" />,
            }}
          />
        ) : null}

        {hasWinConditionAchievements && !hasProgressionAchievements ? (
          <Trans
            i18nKey="Unlock <1>ANY</1> win condition achievement."
            components={{
              1: <span className="font-semibold text-amber-500" />,
            }}
          />
        ) : null}
      </BaseAlertDescription>
    </BaseAlert>
  );
};
