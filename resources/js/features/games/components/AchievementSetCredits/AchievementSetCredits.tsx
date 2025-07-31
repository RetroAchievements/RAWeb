import { type FC } from 'react';

import { AchievementAuthorsDisplay } from './AchievementAuthorsDisplay';
import { ArtworkCreditsDisplay } from './ArtworkCreditsDisplay';
import { ClaimantsDisplay } from './ClaimantsDisplay';
import { CodeCreditsDisplay } from './CodeCreditsDisplay';
import { DesignCreditsDisplay } from './DesignCreditsDisplay';
import { MobileCreditDialogTrigger } from './MobileCreditDialogTrigger';
import { useAchievementSetCredits } from './useAchievementSetCredits';

export const AchievementSetCredits: FC = () => {
  const {
    achievementSetClaims,
    aggregateCredits,
    artCreditUsers,
    codingCreditUsers,
    designCreditUsers,
  } = useAchievementSetCredits();

  if (
    !aggregateCredits.achievementsAuthors.length &&
    !artCreditUsers.length &&
    !codingCreditUsers.length &&
    !designCreditUsers.length &&
    !achievementSetClaims.length
  ) {
    return null;
  }

  return (
    <div
      data-testid="set-credits"
      className="flex items-center justify-between text-neutral-300 light:text-neutral-700"
    >
      <MobileCreditDialogTrigger
        achievementSetClaims={achievementSetClaims}
        aggregateCredits={aggregateCredits}
        artCreditUsers={artCreditUsers}
        codingCreditUsers={codingCreditUsers}
        designCreditUsers={designCreditUsers}
      />

      <div className="hidden w-full items-center rounded sm:flex lg:flex-col lg:items-start lg:gap-2 xl:flex-row xl:items-center xl:gap-0">
        <div className="flex flex-wrap items-center gap-x-2 gap-y-1 xl:gap-4">
          {aggregateCredits.achievementsAuthors.length ? (
            <AchievementAuthorsDisplay authors={aggregateCredits.achievementsAuthors} />
          ) : null}

          {achievementSetClaims?.length ? (
            <ClaimantsDisplay achievementSetClaims={achievementSetClaims} />
          ) : null}

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
