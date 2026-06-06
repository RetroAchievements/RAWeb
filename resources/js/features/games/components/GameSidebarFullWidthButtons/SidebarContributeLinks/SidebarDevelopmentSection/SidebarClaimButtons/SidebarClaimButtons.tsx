import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuFlagTriangleRight } from 'react-icons/lu';

import { PlayableSidebarButton } from '@/common/components/PlayableSidebarButton';
import { usePageProps } from '@/common/hooks/usePageProps';
import { ClaimConfirmationDialog } from '@/features/games/components/ClaimConfirmationDialog';
import { useCanShowCreateClaimButton } from '@/features/games/hooks/useCanShowCreateClaimButton';
import { getAllPageAchievements } from '@/features/games/utils/getAllPageAchievements';

export const SidebarClaimButtons: FC = () => {
  const { achievementSetClaims, backingGame, claimData, game, targetAchievementSetId } =
    usePageProps<App.Platform.Data.GameShowPageProps>();
  const { t } = useTranslation();

  const canShowCreateClaimButton = useCanShowCreateClaimButton();

  const areAnyClaimsInReview = achievementSetClaims.some((c) => c.status === 'in_review');
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
      {canShowCreateClaimButton ? (
        <ClaimConfirmationDialog
          action="create"
          trigger={
            <PlayableSidebarButton
              IconComponent={LuFlagTriangleRight}
              showSubsetIndicator={game.id !== backingGame.id}
            >
              {wouldBeRevisionClaim
                ? t('Create New Revision Claim')
                : claimData?.wouldBeCollaboration
                  ? t('Create New Collaboration Claim')
                  : t('Create New Claim')}
            </PlayableSidebarButton>
          }
        />
      ) : null}

      {claimData?.userClaim?.isCompletable ? (
        <ClaimConfirmationDialog
          action="complete"
          trigger={
            <PlayableSidebarButton
              IconComponent={LuFlagTriangleRight}
              showSubsetIndicator={game.id !== backingGame.id}
            >
              {t('Complete Claim')}
            </PlayableSidebarButton>
          }
        />
      ) : null}

      {claimData?.userClaim ? (
        <>
          {claimData?.userClaim?.isExtendable ? (
            <ClaimConfirmationDialog
              action="extend"
              trigger={
                <PlayableSidebarButton
                  IconComponent={LuFlagTriangleRight}
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
                  IconComponent={LuFlagTriangleRight}
                  showSubsetIndicator={game.id !== backingGame.id}
                >
                  {t('Drop Claim')}
                </PlayableSidebarButton>
              }
            />
          ) : null}
        </>
      ) : null}
    </>
  );
};
