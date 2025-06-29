import { type FC } from 'react';

import { usePageProps } from '@/common/hooks/usePageProps';

import { AchievementAuthorsDisplay } from './AchievementAuthorsDisplay';
import { ArtworkCreditsDisplay } from './ArtworkCreditsDisplay';
import { ClaimantsDisplay } from './ClaimantsDisplay';
import { CodeCreditsDisplay } from './CodeCreditsDisplay';
import { DesignCreditsDisplay } from './DesignCreditsDisplay';

export const AchievementSetCredits: FC = () => {
  const { achievementSetClaims, aggregateCredits } =
    usePageProps<App.Platform.Data.GameShowPageProps>();

  if (!achievementSetClaims?.length && !aggregateCredits) {
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
  ].filter(
    (user, index, self) => index === self.findIndex((u) => u.displayName === user.displayName),
  );

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
      className="hidden items-center justify-between text-neutral-300 light:text-neutral-700 sm:flex"
    >
      <div className="flex w-full items-center rounded lg:flex-col lg:items-start lg:gap-2 xl:flex-row xl:items-center xl:gap-0">
        <div className="flex flex-wrap items-center gap-x-2 gap-y-1 xl:gap-4">
          {aggregateCredits.achievementsAuthors.length ? (
            <AchievementAuthorsDisplay authors={aggregateCredits.achievementsAuthors} />
          ) : null}

          {achievementSetClaims.length ? (
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
              testingCredits={aggregateCredits.achievementsTesting}
              writingCredits={aggregateCredits.achievementsWriting}
            />
          ) : null}
        </div>
      </div>
    </div>
  );
};
