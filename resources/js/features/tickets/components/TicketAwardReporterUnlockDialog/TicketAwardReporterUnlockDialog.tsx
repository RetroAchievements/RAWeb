import { router } from '@inertiajs/react';
import { type FC, type ReactNode, useState } from 'react';
import { Trans, useTranslation } from 'react-i18next';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import {
  BaseDialog,
  BaseDialogClose,
  BaseDialogContent,
  BaseDialogDescription,
  BaseDialogFooter,
  BaseDialogHeader,
  BaseDialogTitle,
  BaseDialogTrigger,
} from '@/common/components/+vendor/BaseDialog';
import { toastMessage } from '@/common/components/+vendor/BaseToaster';
import type { PlayMode } from '@/common/models';
import type { TranslationKey } from '@/types/i18next';

import { useAwardReporterUnlockMutation } from '../../hooks/mutations/useAwardReporterUnlockMutation';

interface AwardDialogCopy {
  body: TranslationKey;
  cta: TranslationKey;
  heading: TranslationKey;
}

const awardDialogCopy = {
  casual: {
    heading: 'Award a casual unlock?',
    body: '<1>{{user}}</1> will get a casual unlock for <2>{{achievement}}</2>.',
    cta: 'Award casual',
  },
  hardcore: {
    heading: 'Award a hardcore unlock?',
    body: '<1>{{user}}</1> will get a hardcore unlock for <2>{{achievement}}</2>.',
    cta: 'Award hardcore',
  },
} as const satisfies Record<PlayMode, AwardDialogCopy>;

interface TicketAwardReporterUnlockDialogProps {
  reporter: App.Data.User;
  ticket: App.Platform.Data.TicketListEntry;
  trigger: ReactNode;
  unlockMode: PlayMode;
}

export const TicketAwardReporterUnlockDialog: FC<TicketAwardReporterUnlockDialogProps> = ({
  reporter,
  ticket,
  trigger,
  unlockMode,
}) => {
  const { t } = useTranslation();

  const [isDialogOpen, setIsDialogOpen] = useState(false);

  const awardReporterUnlockMutation = useAwardReporterUnlockMutation();

  const modeCopy = awardDialogCopy[unlockMode];

  const handleAwardConfirm = () => {
    toastMessage.promise(
      awardReporterUnlockMutation.mutateAsync({
        ticketId: ticket.id,
        payload: { isHardcore: unlockMode === 'hardcore' },
      }),
      {
        loading: t('Awarding...'),
        success: () => {
          setIsDialogOpen(false);
          router.reload({ only: ['reporterUnlock', 'hasReporterPlayedGame', 'reporterActivity'] });

          return t('Awarded!');
        },
        error: t('Something went wrong.'),
      },
    );
  };

  return (
    <BaseDialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
      <BaseDialogTrigger asChild>{trigger}</BaseDialogTrigger>

      <BaseDialogContent>
        <BaseDialogHeader>
          <BaseDialogTitle>{t(modeCopy.heading)}</BaseDialogTitle>

          <BaseDialogDescription>
            <Trans
              i18nKey={modeCopy.body}
              values={{ user: reporter.displayName, achievement: ticket.ticketableTitle }}
              components={{
                1: <span className="font-bold" />,
                2: <span className="font-bold" />,
              }}
            />
          </BaseDialogDescription>
        </BaseDialogHeader>

        <BaseDialogFooter className="mt-4">
          <BaseDialogClose asChild>
            <BaseButton variant="link" size="sm">
              {t('Cancel')}
            </BaseButton>
          </BaseDialogClose>

          <BaseButton
            size="sm"
            onClick={handleAwardConfirm}
            disabled={awardReporterUnlockMutation.isPending}
          >
            {t(modeCopy.cta)}
          </BaseButton>
        </BaseDialogFooter>
      </BaseDialogContent>
    </BaseDialog>
  );
};
