import type { FC } from 'react';
import { Trans, useTranslation } from 'react-i18next';

import { GameTitle } from '@/common/components/GameTitle';
import { usePageProps } from '@/common/hooks/usePageProps';
import {
  ForumTopicNotice,
  ProgressReportWarning,
  QuickCompletionWarning,
  RevisionPlanWarning,
  SubsetApprovalWarning,
  UnresolvedTicketsWarning,
} from '@/features/games/components/ClaimDialogWarnings/ClaimDialogWarnings';
import type { ClaimActionType } from '@/features/games/hooks/useClaimActions';
import { getAllPageAchievements } from '@/features/games/utils/getAllPageAchievements';

interface ClaimDialogDescriptionsProps {
  action: ClaimActionType;
}

export const ClaimDialogDescriptions: FC<ClaimDialogDescriptionsProps> = ({ action }) => {
  const { auth, backingGame, claimData, game, targetAchievementSetId } =
    usePageProps<App.Platform.Data.GameShowPageProps>();
  const { t } = useTranslation();

  const allPageAchievements = getAllPageAchievements(
    game.gameAchievementSets!,
    targetAchievementSetId,
  );
  const wouldBeRevisionClaim = allPageAchievements.length > 0;
  const wouldBeSubsetClaim = game.id !== backingGame.id;
  const isSoleAuthor = allPageAchievements.every(
    (a) => a.developer?.displayName === auth!.user.displayName,
  );

  switch (action) {
    case 'create':
      return (
        <span className="flex flex-col gap-2">
          <span>
            <Trans
              i18nKey={
                claimData?.wouldBeCollaboration
                  ? 'This will create a new collaboration claim for <1>{{gameTitle}}</1>.'
                  : 'This will create a new primary claim for <1>{{gameTitle}}</1>.'
              }
              components={{
                1: <GameTitle title={backingGame.title} />,
              }}
            />
          </span>

          {wouldBeRevisionClaim && !isSoleAuthor ? <RevisionPlanWarning /> : null}
          {wouldBeSubsetClaim && !wouldBeRevisionClaim ? <SubsetApprovalWarning /> : null}
          {claimData?.numUnresolvedTickets ? <UnresolvedTicketsWarning /> : null}
          {!game.forumTopicId ? <ForumTopicNotice /> : null}
        </span>
      );

    case 'drop':
      return (
        <span>
          <Trans
            i18nKey="This will drop your current claim for <1>{{gameTitle}}</1>."
            components={{
              1: <GameTitle title={backingGame.title} />,
            }}
          />
        </span>
      );

    case 'extend':
      return (
        <span className="flex flex-col gap-2">
          <span>{t('This will extend the claim for another three months.')}</span>
          <ProgressReportWarning />
        </span>
      );

    case 'complete':
      return (
        <span className="flex flex-col gap-2">
          <span>
            {t('This will inform all set requestors that new achievements have been added.')}
          </span>
          <QuickCompletionWarning minutesActive={claimData?.userClaim?.minutesActive} />
        </span>
      );

    default:
      return null;
  }
};
