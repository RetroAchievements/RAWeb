import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuWrench } from 'react-icons/lu';

import { PlayableSidebarButton } from '@/common/components/PlayableSidebarButton';
import { usePageProps } from '@/common/hooks/usePageProps';
import { cn } from '@/common/utils/cn';
import { ClaimStatus } from '@/common/utils/generatedAppConstants';
import { ClaimConfirmationDialog } from '@/features/games/components/ClaimConfirmationDialog';
import { getAllPageAchievements } from '@/features/games/utils/getAllPageAchievements';

export const SidebarClaimButtons: FC = () => {
  const { achievementSetClaims, backingGame, can, claimData, game, targetAchievementSetId } =
    usePageProps<App.Platform.Data.GameShowPageProps>();
  const { t } = useTranslation();

  const areAnyClaimsInReview = achievementSetClaims.some((c) => c.status === ClaimStatus.InReview);
  if (areAnyClaimsInReview) {
    return null;
  }

  const allPageAchievements = getAllPageAchievements(
    game.gameAchievementSets!,
    targetAchievementSetId,
  );
  const wouldBeRevisionClaim = allPageAchievements.length > 0;

  return (
    <>
      {can.createAchievementSetClaims &&
      (claimData?.numClaimsRemaining || claimData?.isSoleAuthor) &&
      !claimData.userClaim ? (
        <ClaimConfirmationDialog
          action="create"
          trigger={
            <PlayableSidebarButton
              IconComponent={LuWrench}
              showSubsetIndicator={game.id !== backingGame.id}
            >
              {wouldBeRevisionClaim ? t('Create New Revision Claim') : t('Create New Claim')}
            </PlayableSidebarButton>
          }
        />
      ) : null}

      {claimData?.userClaim?.isCompletable ? (
        <ClaimConfirmationDialog
          action="complete"
          trigger={
            <PlayableSidebarButton
              IconComponent={LuWrench}
              showSubsetIndicator={game.id !== backingGame.id}
            >
              {t('Complete Claim')}
            </PlayableSidebarButton>
          }
        />
      ) : null}

      {claimData?.userClaim ? (
        <div
          className={cn(
            'grid gap-1',
            claimData?.userClaim?.isExtendable && claimData?.userClaim?.isDroppable
              ? 'grid-cols-2'
              : null,
          )}
        >
          {claimData?.userClaim?.isExtendable ? (
            <ClaimConfirmationDialog
              action="extend"
              trigger={
                <PlayableSidebarButton
                  IconComponent={LuWrench}
                  showSubsetIndicator={game.id !== backingGame.id}
                >
                  {t('Extend Claim')}
                </PlayableSidebarButton>
              }
            />
          ) : null}

          {claimData?.userClaim?.isDroppable ? (
            <ClaimConfirmationDialog
              action="drop"
              trigger={
                <PlayableSidebarButton
                  IconComponent={LuWrench}
                  showSubsetIndicator={game.id !== backingGame.id}
                >
                  {t('Drop Claim')}
                </PlayableSidebarButton>
              }
            />
          ) : null}
        </div>
      ) : null}
    </>
  );
};
