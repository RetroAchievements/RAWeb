import { useMemo } from 'react';

import { usePageProps } from '@/common/hooks/usePageProps';

export function useAchievementSetCredits() {
  const { achievementSetClaims, aggregateCredits } =
    usePageProps<App.Platform.Data.GameShowPageProps>();

  const artCreditUsers = useMemo(() => {
    return [
      ...aggregateCredits.achievementSetArtwork,
      ...aggregateCredits.achievementsArtwork,
    ].filter(
      (user, index, self) => index === self.findIndex((u) => u.displayName === user.displayName),
    );
  }, [aggregateCredits.achievementSetArtwork, aggregateCredits.achievementsArtwork]);

  const logicCreditUsers = useMemo(() => {
    return aggregateCredits.achievementsLogic.filter(
      (logicUser) =>
        !aggregateCredits.achievementsAuthors.some(
          (author) => author.displayName === logicUser.displayName,
        ),
    );
  }, [aggregateCredits.achievementsAuthors, aggregateCredits.achievementsLogic]);

  const codingCreditUsers = useMemo(() => {
    return [...aggregateCredits.achievementsMaintainers, ...logicCreditUsers].filter(
      (user, index, self) => index === self.findIndex((u) => u.displayName === user.displayName),
    );
  }, [aggregateCredits.achievementsMaintainers, logicCreditUsers]);

  const designCreditUsers = useMemo(() => {
    return [
      ...aggregateCredits.achievementsDesign,
      ...aggregateCredits.achievementsTesting,
      ...aggregateCredits.achievementsWriting,
      ...aggregateCredits.hashCompatibilityTesting,
    ].filter(
      (user, index, self) => index === self.findIndex((u) => u.displayName === user.displayName),
    );
  }, [
    aggregateCredits.achievementsDesign,
    aggregateCredits.achievementsTesting,
    aggregateCredits.achievementsWriting,
    aggregateCredits.hashCompatibilityTesting,
  ]);

  return {
    achievementSetClaims,
    aggregateCredits,
    artCreditUsers,
    codingCreditUsers,
    designCreditUsers,
  };
}
