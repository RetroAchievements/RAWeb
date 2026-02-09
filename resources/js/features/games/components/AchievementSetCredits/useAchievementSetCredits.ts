import { useMemo } from 'react';

import { usePageProps } from '@/common/hooks/usePageProps';

import { deduplicateUserCredits } from './deduplicateUserCredits';

export function useAchievementSetCredits() {
  const { achievementSetClaims, aggregateCredits } =
    usePageProps<App.Platform.Data.GameShowPageProps>();

  const artCreditUsers = useMemo(
    () =>
      deduplicateUserCredits([
        ...aggregateCredits.achievementSetArtwork,
        ...aggregateCredits.achievementSetBanner,
        ...aggregateCredits.achievementsArtwork,
      ]),
    [
      aggregateCredits.achievementSetArtwork,
      aggregateCredits.achievementSetBanner,
      aggregateCredits.achievementsArtwork,
    ],
  );

  const codingCreditUsers = useMemo(
    () =>
      deduplicateUserCredits([
        ...aggregateCredits.achievementsMaintainers,
        ...aggregateCredits.achievementsLogic,
      ]),
    [aggregateCredits.achievementsMaintainers, aggregateCredits.achievementsLogic],
  );

  const designCreditUsers = useMemo(
    () =>
      deduplicateUserCredits([
        ...aggregateCredits.achievementsDesign,
        ...aggregateCredits.achievementsTesting,
        ...aggregateCredits.achievementsWriting,
        ...aggregateCredits.hashCompatibilityTesting,
      ]),
    [
      aggregateCredits.achievementsDesign,
      aggregateCredits.achievementsTesting,
      aggregateCredits.achievementsWriting,
      aggregateCredits.hashCompatibilityTesting,
    ],
  );

  return {
    achievementSetClaims,
    aggregateCredits,
    artCreditUsers,
    codingCreditUsers,
    designCreditUsers,
  };
}
