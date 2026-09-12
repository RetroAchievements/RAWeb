import { type FC, type ReactNode, useState } from 'react';
import { useTranslation } from 'react-i18next';

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
import { BaseLabel } from '@/common/components/+vendor/BaseLabel';
import { BaseSelectNative } from '@/common/components/+vendor/BaseSelectNative';
import { toastMessage } from '@/common/components/+vendor/BaseToaster';
import { useMarkClaimReleaseScheduledMutation } from '@/features/games/hooks/mutations/useMarkClaimReleaseScheduledMutation';

interface MarkClaimPartCompleteDialogProps {
  claims: App.Platform.Data.AchievementSetClaim[];
  trigger: ReactNode;
}

export const MarkClaimPartCompleteDialog: FC<MarkClaimPartCompleteDialogProps> = ({
  claims,
  trigger,
}) => {
  const { t } = useTranslation();

  const [isOpen, setIsOpen] = useState(false);
  const [selectedClaimId, setSelectedClaimId] = useState('');

  const markClaimReleaseScheduledMutation = useMarkClaimReleaseScheduledMutation();

  const sortedClaims = [...claims].sort(
    (a, b) => Number(b.claimType === 'primary') - Number(a.claimType === 'primary'),
  );

  const handleOpenChange = (open: boolean) => {
    setIsOpen(open);

    if (!open) {
      setSelectedClaimId('');
    }
  };

  const handleConfirmClick = async () => {
    const claimId = Number(selectedClaimId);
    handleOpenChange(false);

    await toastMessage.promise(markClaimReleaseScheduledMutation.mutateAsync({ claimId }), {
      loading: t('Marking part complete...'),
      success: t('Marked!'),
      error: t('Something went wrong.'),
    });
  };

  return (
    <BaseDialog open={isOpen} onOpenChange={handleOpenChange}>
      <BaseDialogTrigger asChild>{trigger}</BaseDialogTrigger>

      <BaseDialogContent>
        <BaseDialogHeader>
          <BaseDialogTitle>{t('Mark claim part complete?')}</BaseDialogTitle>
          <BaseDialogDescription>
            {t(
              "The selected claim will be marked as Release Scheduled and will no longer use one of that developer's claim slots.",
            )}
          </BaseDialogDescription>
        </BaseDialogHeader>

        <div className="my-3 flex flex-col gap-1">
          <BaseLabel className="text-neutral-300 light:text-neutral-700" htmlFor="claimant-select">
            {t('Select a claimant')}
          </BaseLabel>

          <BaseSelectNative
            id="claimant-select"
            value={selectedClaimId}
            onChange={(event) => setSelectedClaimId(event.target.value)}
          >
            <option value="" disabled>
              {t('Select a claimant')}
            </option>

            {sortedClaims.map((claim) => (
              <option key={`claimant-${claim.id}`} value={claim.id}>
                {claim.claimType === 'primary'
                  ? t('{{displayName}} (Primary)', { displayName: claim.user?.displayName })
                  : t('{{displayName}} (Collaboration)', {
                      displayName: claim.user?.displayName,
                    })}
              </option>
            ))}
          </BaseSelectNative>
        </div>

        <BaseDialogFooter>
          <BaseDialogClose asChild>
            <BaseButton variant="link" size="sm">
              {t('Cancel')}
            </BaseButton>
          </BaseDialogClose>

          <BaseButton
            className="min-w-40"
            onClick={handleConfirmClick}
            disabled={selectedClaimId === ''}
            size="sm"
          >
            {t('Mark part complete')}
          </BaseButton>
        </BaseDialogFooter>
      </BaseDialogContent>
    </BaseDialog>
  );
};
