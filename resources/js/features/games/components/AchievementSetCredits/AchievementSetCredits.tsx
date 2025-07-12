import { type FC } from 'react';

import { usePageProps } from '@/common/hooks/usePageProps';

import { AchievementAuthorsDisplay } from './AchievementAuthorsDisplay';
import { ArtworkCreditsDisplay } from './ArtworkCreditsDisplay';
import { CodeCreditsDisplay } from './CodeCreditsDisplay';
import { DesignCreditsDisplay } from './DesignCreditsDisplay';

export const AchievementSetCredits: FC = () => {
  const { aggregateCredits } = usePageProps<App.Platform.Data.GameShowPageProps>();

  if (!aggregateCredits) {
    return null;
  }

  const artCreditUsers = [
    ...aggregateCredits.achievementSetArtwork,
    ...aggregateCredits.achievementsArtwork,
  ].filter(
    (user, index, self) => index === self.findIndex((u) => u.displayName === user.displayName),
  );

  const logicCreditUsers = aggregateCredits.achievementsLogic.filter(
    (logicUser) =>
      !aggregateCredits.achievementsAuthors.some(
        (author) => author.displayName === logicUser.displayName,
      ),
  );
  const codingCreditUsers = [
    ...aggregateCredits.achievementsMaintainers,
    ...logicCreditUsers,
  ].filter(
    (user, index, self) => index === self.findIndex((u) => u.displayName === user.displayName),
  );

  const designCreditUsers = [
    ...aggregateCredits.achievementsDesign,
    ...aggregateCredits.achievementsTesting,
    ...aggregateCredits.achievementsWriting,
    ...aggregateCredits.hashCompatibilityTesting,
  ].filter(
    (user, index, self) => index === self.findIndex((u) => u.displayName === user.displayName),
  );

  return (
    <div
      data-testid="set-credits"
      className="hidden items-center justify-between text-neutral-300 light:text-neutral-700 sm:flex"
    >
      <div className="flex w-full items-center rounded py-1 lg:flex-col lg:items-start lg:gap-2 xl:flex-row xl:items-center xl:gap-0">
        <div className="flex flex-wrap items-center gap-x-2 gap-y-1 xl:gap-4">
          <AchievementAuthorsDisplay authors={aggregateCredits.achievementsAuthors} />

          {artCreditUsers.length ? (
            <ArtworkCreditsDisplay
              achievementArtworkCredits={aggregateCredits.achievementsArtwork}
              badgeArtworkCredits={aggregateCredits.achievementSetArtwork}
            />
          ) : null}

          {codingCreditUsers.length ? (
            <CodeCreditsDisplay
              authorCredits={aggregateCredits.achievementsAuthors}
              logicCredits={aggregateCredits.achievementsLogic}
              maintainerCredits={aggregateCredits.achievementsMaintainers}
            />
          ) : null}

          {designCreditUsers.length ? (
            <DesignCreditsDisplay
              designCredits={aggregateCredits.achievementsDesign}
              hashCompatibilityTestingCredits={aggregateCredits.hashCompatibilityTesting}
              testingCredits={aggregateCredits.achievementsTesting}
              writingCredits={aggregateCredits.achievementsWriting}
            />
          ) : null}
        </div>
      </div>
    </div>
  );
};
