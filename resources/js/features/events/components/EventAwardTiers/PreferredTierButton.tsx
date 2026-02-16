import { router } from '@inertiajs/react';
import { type FC, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { LuSettings2 } from 'react-icons/lu';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import { BaseDialog } from '@/common/components/+vendor/BaseDialog';
import {
  BaseTooltip,
  BaseTooltipContent,
  BaseTooltipTrigger,
} from '@/common/components/+vendor/BaseTooltip';
import { usePageProps } from '@/common/hooks/usePageProps';

import { PreferredTierDialogContent } from './PreferredTierDialogContent';

export const PreferredTierButton: FC = () => {
  const { auth, event, preferredEventAwardTier, earnedEventAwardTier } =
    usePageProps<App.Platform.Data.EventShowPageProps>();
  const { t } = useTranslation();

  const [isDialogOpen, setIsDialogOpen] = useState(false);

  const eventAwards = event.eventAwards ?? [];

  // Don't show if user isn't authenticated or there are fewer than 2 tiers.
  if (!auth?.user || eventAwards.length < 2) {
    return null;
  }

  // The user needs to have earned at least 2 tiers to have a meaningful choice.
  if (earnedEventAwardTier === null || earnedEventAwardTier < 1) {
    return null;
  }

  // The user's current display preference, or the highest earned tier if unset.
  const currentTierIndex = preferredEventAwardTier ?? earnedEventAwardTier;

  const handleSubmitSuccess = () => {
    setIsDialogOpen(false);
    router.reload({ only: ['preferredEventAwardTier'] });
  };

  return (
    <>
      <BaseTooltip>
        <BaseTooltipTrigger asChild>
          <BaseButton
            size="icon"
            className="size-6 min-w-6"
            aria-label={t('Preferred Event Award')}
            onClick={() => setIsDialogOpen(true)}
          >
            <LuSettings2 className="size-3.5" />
          </BaseButton>
        </BaseTooltipTrigger>

        <BaseTooltipContent>{t('Preferred Event Award')}</BaseTooltipContent>
      </BaseTooltip>

      <BaseDialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
        <PreferredTierDialogContent
          earnedTierIndex={earnedEventAwardTier}
          event={event}
          eventAwards={eventAwards}
          initialTierIndex={currentTierIndex}
          onSubmitSuccess={handleSubmitSuccess}
        />
      </BaseDialog>
    </>
  );
};
