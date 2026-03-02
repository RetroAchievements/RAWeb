import { usePageProps } from '@/common/hooks/usePageProps';

import { deduplicateUserCredits } from './deduplicateUserCredits';

export function useAchievementSetCredits() {
  const { achievementSetClaims, aggregateCredits } =
    usePageProps<App.Platform.Data.GameShowPageProps>();

  const artCreditUsers = deduplicateUserCredits([
    ...aggregateCredits.achievementSetArtwork,
    ...aggregateCredits.achievementSetBanner,
    ...aggregateCredits.achievementsArtwork,
  ]);

  const codingCreditUsers = deduplicateUserCredits([
    ...aggregateCredits.achievementsMaintainers,
    ...aggregateCredits.achievementsLogic,
  ]);

  const designCreditUsers = deduplicateUserCredits([
    ...aggregateCredits.achievementsDesign,
    ...aggregateCredits.achievementsTesting,
    ...aggregateCredits.achievementsWriting,
    ...aggregateCredits.hashCompatibilityTesting,
  ]);

  return {
    achievementSetClaims,
    aggregateCredits,
    artCreditUsers,
    codingCreditUsers,
    designCreditUsers,
  };
}
