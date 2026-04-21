import { usePageProps } from '@/common/hooks/usePageProps';
import { UserRole } from '@/common/utils/generatedAppConstants';

import { getCanCreateClaim } from '../utils/getCanCreateClaim';

export function useCanShowCreateClaimButton(): boolean {
  const { auth, backingGame, claimData } = usePageProps<App.Platform.Data.GameShowPageProps>();

  const user = auth?.user;
  if (!user || claimData?.userClaim) {
    return false;
  }

  const isJuniorDev = user.roles.includes(UserRole.DEVELOPER_JUNIOR);
  const hasClaimRole = isJuniorDev || user.roles.includes(UserRole.DEVELOPER);
  if (!hasClaimRole) {
    return false;
  }

  // Junior devs can only create claims on games with official forum topics.
  if (isJuniorDev && !backingGame.forumTopicId) {
    return false;
  }

  return getCanCreateClaim(claimData);
}
