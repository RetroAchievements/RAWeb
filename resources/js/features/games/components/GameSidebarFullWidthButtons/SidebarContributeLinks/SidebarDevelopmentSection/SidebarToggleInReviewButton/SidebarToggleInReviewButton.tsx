import { type FC, useMemo, useState } from 'react';
import { Trans, useTranslation } from 'react-i18next';
import { LuLock, LuLockOpen } from 'react-icons/lu';

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
import { GameTitle } from '@/common/components/GameTitle';
import { PlayableSidebarButton } from '@/common/components/PlayableSidebarButton';
import { usePageProps } from '@/common/hooks/usePageProps';
import { useUpdateClaimStatusMutation } from '@/features/games/hooks/mutations/useUpdateClaimStatusMutation';

export const SidebarToggleInReviewButton: FC = () => {
  const { achievementSetClaims, backingGame, can, game } =
    usePageProps<App.Platform.Data.GameShowPageProps>();
  const { t } = useTranslation();

  const [isDialogOpen, setIsDialogOpen] = useState(false);

  const updateStatusMutation = useUpdateClaimStatusMutation();

  // Find the primary claim from the claims list.
  const primaryClaim = useMemo(() => {
    return achievementSetClaims.find(
      (claim) =>
        claim.claimType === 'primary' &&
        (claim.status === 'active' || claim.status === 'in_review'),
    );
  }, [achievementSetClaims]);

  // If there's no primary claim or the user can't toggle review status for claims, bail.
  if (!can.reviewAchievementSetClaims || !primaryClaim) {
    return null;
  }

  const isInReview = primaryClaim.status === 'in_review';

  const handleConfirmClick = async () => {
    setIsDialogOpen(false);

    const newStatus = isInReview ? 'active' : 'in_review';
    const loadingMessage = isInReview
      ? t('Completing the review...')
      : t('Marking the claim for review...');

    const successMessage = isInReview
      ? t('Completed the review!')
      : t('Marked the claim for review!');

    await toastMessage.promise(
      updateStatusMutation.mutateAsync({
        claimId: primaryClaim.id,
        status: newStatus,
      }),
      {
        loading: loadingMessage,
        success: successMessage,
        error: t('Something went wrong.'),
      },
    );
  };

  const buttonText = isInReview ? t('Complete Claim Review') : t('Mark Claim for Review');

  return (
    <BaseDialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
      <BaseDialogTrigger asChild>
        <PlayableSidebarButton
          IconComponent={isInReview ? LuLockOpen : LuLock}
          showSubsetIndicator={game.id !== backingGame.id}
        >
          {buttonText}
        </PlayableSidebarButton>
      </BaseDialogTrigger>

      <BaseDialogContent>
        <BaseDialogHeader>
          <BaseDialogTitle>{t('Are you sure?')}</BaseDialogTitle>
          <BaseDialogDescription>
            <span>
              <Trans
                i18nKey={
                  isInReview
                    ? 'This will complete the review for <1>{{gameTitle}}</1>. The Junior Developer will be able to complete or drop their claim.'
                    : "This will mark the active claim for <1>{{gameTitle}}</1> as In Review. The Junior Developer will not be able to complete or drop their claim while it's being reviewed."
                }
                components={{
                  1: <GameTitle title={game.title} />,
                }}
              />
            </span>
          </BaseDialogDescription>
        </BaseDialogHeader>

        <BaseDialogFooter>
          <BaseDialogClose asChild>
            <BaseButton variant="link">{t('Nevermind')}</BaseButton>
          </BaseDialogClose>

          <BaseButton onClick={handleConfirmClick}>
            {isInReview ? t('Yes, complete the review') : t('Yes, begin the review')}
          </BaseButton>
        </BaseDialogFooter>
      </BaseDialogContent>
    </BaseDialog>
  );
};
