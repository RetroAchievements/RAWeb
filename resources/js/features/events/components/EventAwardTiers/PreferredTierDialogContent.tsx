import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import {
  BaseDialogContent,
  BaseDialogDescription,
  BaseDialogHeader,
  BaseDialogTitle,
} from '@/common/components/+vendor/BaseDialog';

import { PreferredTierForm } from './PreferredTierForm';

interface PreferredTierDialogContentProps {
  earnedTierIndex: number;
  event: App.Platform.Data.Event;
  eventAwards: App.Platform.Data.EventAward[];
  initialTierIndex: number;
  onSubmitSuccess: () => void;
}

export const PreferredTierDialogContent: FC<PreferredTierDialogContentProps> = ({
  earnedTierIndex,
  event,
  eventAwards,
  initialTierIndex,
  onSubmitSuccess,
}) => {
  const { t } = useTranslation();

  return (
    <BaseDialogContent>
      <BaseDialogHeader className="pb-3">
        <BaseDialogTitle>{t('Preferred Event Award')}</BaseDialogTitle>
        <BaseDialogDescription>
          {t('Choose which event award badge to display on your profile.')}
        </BaseDialogDescription>
      </BaseDialogHeader>

      <PreferredTierForm
        earnedTierIndex={earnedTierIndex}
        event={event}
        eventAwards={eventAwards}
        initialTierIndex={initialTierIndex}
        onSubmitSuccess={onSubmitSuccess}
      />
    </BaseDialogContent>
  );
};
