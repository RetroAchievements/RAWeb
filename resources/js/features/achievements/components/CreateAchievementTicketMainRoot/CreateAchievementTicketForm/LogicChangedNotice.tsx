import type { FC } from 'react';
import { useFormContext, useWatch } from 'react-hook-form';
import { useTranslation } from 'react-i18next';

import {
  BaseAlert,
  BaseAlertDescription,
  BaseAlertTitle,
} from '@/common/components/+vendor/BaseAlert';
import { usePageProps } from '@/common/hooks/usePageProps';

import type { CreateAchievementTicketFormValues } from './useCreateAchievementTicketForm';

export const LogicChangedNotice: FC = () => {
  const { achievement, didLogicChangeSinceLastPlayed } =
    usePageProps<App.Platform.Data.CreateAchievementTicketPageProps>();
  const { t } = useTranslation();

  const form = useFormContext<CreateAchievementTicketFormValues>();
  const issue = useWatch({ name: 'issue', control: form.control });

  if (!didLogicChangeSinceLastPlayed || issue !== 'DidNotTrigger') {
    return null;
  }

  const hasUnlocked = !!achievement.unlockedAt || !!achievement.unlockedHardcoreAt;

  return (
    <div className="pt-2">
      <BaseAlert variant="notice" className="bg-embed light:bg-neutral-50">
        <BaseAlertTitle className="font-bold">
          {t('The logic for this achievement has changed since you last played.')}
        </BaseAlertTitle>

        {!hasUnlocked ? (
          <BaseAlertDescription>
            {t('Reload the game and try again. If it still happens, tell us below.')}
          </BaseAlertDescription>
        ) : null}
      </BaseAlert>
    </div>
  );
};
