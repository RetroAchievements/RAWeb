import { usePageProps } from '@/common/hooks/usePageProps';
import type { CreateClaimDialogVariant } from '@/features/games/models';

import { getAllPageAchievements } from '../utils/getAllPageAchievements';
import type { ClaimActionType } from './useClaimActions';

interface ClaimDialogState {
  createClaimDialogVariant: CreateClaimDialogVariant;
  hasDialogNotice: boolean;
  hasForumTopicNotice: boolean;
  hasQuickCompletionWarning: boolean;
  hasRevisionPlanNotice: boolean;
  hasSubsetApprovalNotice: boolean;
  quickCompletionMinutesActive?: number;
  requiresTicketAcknowledgment: boolean;
  unresolvedTicketCount: number;
}

export function useClaimDialogState(action: ClaimActionType): ClaimDialogState {
  const { auth, backingGame, claimData, game, targetAchievementSetId } =
    usePageProps<App.Platform.Data.GameShowPageProps>();

  const unresolvedTicketCount = claimData?.numUnresolvedTickets ?? 0;
  const quickCompletionMinutesActive = claimData?.userClaim?.minutesActive;
  const hasQuickCompletionWarning =
    !!quickCompletionMinutesActive && quickCompletionMinutesActive <= 1440;

  if (action !== 'create') {
    return {
      createClaimDialogVariant: 'newSet',
      hasDialogNotice: action === 'extend' || (action === 'complete' && hasQuickCompletionWarning),
      hasForumTopicNotice: false,
      hasQuickCompletionWarning,
      hasRevisionPlanNotice: false,
      hasSubsetApprovalNotice: false,
      quickCompletionMinutesActive,
      requiresTicketAcknowledgment: false,
      unresolvedTicketCount,
    };
  }

  const pageGame = game ?? backingGame;
  const allPageAchievements = getAllPageAchievements(
    game?.gameAchievementSets ?? [],
    targetAchievementSetId,
  );
  const wouldBeRevisionClaim = allPageAchievements.length > 0;
  const wouldBeSubsetClaim = pageGame.id !== backingGame.id;
  const currentUserDisplayName = auth?.user.displayName;
  const isSoleAuthor = allPageAchievements.every(
    (a) => a.developer?.displayName === currentUserDisplayName,
  );

  const createClaimDialogVariant = getCreateClaimDialogVariant(
    wouldBeRevisionClaim,
    claimData?.wouldBeCollaboration,
  );
  const hasRevisionPlanNotice = createClaimDialogVariant === 'revision' && !isSoleAuthor;
  const hasSubsetApprovalNotice = createClaimDialogVariant === 'newSet' && wouldBeSubsetClaim;
  const hasForumTopicNotice = !pageGame.forumTopicId;
  const requiresTicketAcknowledgment =
    unresolvedTicketCount > 0 && createClaimDialogVariant !== 'collaboration';

  return {
    createClaimDialogVariant,
    hasDialogNotice:
      hasRevisionPlanNotice ||
      hasSubsetApprovalNotice ||
      hasForumTopicNotice ||
      requiresTicketAcknowledgment,
    hasForumTopicNotice,
    hasQuickCompletionWarning: false,
    hasRevisionPlanNotice,
    hasSubsetApprovalNotice,
    quickCompletionMinutesActive,
    requiresTicketAcknowledgment,
    unresolvedTicketCount,
  };
}

function getCreateClaimDialogVariant(
  hasPageAchievements: boolean,
  wouldBeCollaboration?: boolean,
): CreateClaimDialogVariant {
  if (hasPageAchievements) {
    return 'revision';
  }

  if (wouldBeCollaboration) {
    return 'collaboration';
  }

  return 'newSet';
}
