import type { FC } from 'react';

import { Trans } from '@/common/components/Trans';
import { usePageProps } from '@/common/hooks/usePageProps';

// Other elements on the page contain some of the same labels, so we
// need to target specifically by this <p> tag.
export const testId = 'unlock-status-label';

export const UnlockStatusLabel: FC = () => {
  const { achievement, hasSession } =
    usePageProps<App.Platform.Data.ReportAchievementIssuePageProps>();

  // Don't show any label if the user has never loaded the game.
  if (!hasSession) {
    return null;
  }

  if (!achievement.unlockedAt && !achievement.unlockedHardcoreAt) {
    return (
      <p data-testid={testId}>
        <Trans i18nKey="You <0>have not</0> unlocked this achievement.">
          {'You'} <span className="font-bold">{'have not'}</span> {'unlocked this achievement.'}
        </Trans>
      </p>
    );
  }

  if (achievement.unlockedHardcoreAt) {
    return (
      <p data-testid={testId}>
        <Trans i18nKey="You <0>have</0> unlocked this achievement.">
          {'You'} <span className="font-bold">{'have'}</span> {'unlocked this achievement.'}
        </Trans>
      </p>
    );
  }

  return (
    <p data-testid={testId}>
      <Trans i18nKey="You <0>have</0> unlocked this achievement <1>in softcore</1>.">
        {'You'} <span className="font-bold">{'have'}</span> {'unlocked this achievement '}
        <span className="font-bold">{'in softcore'}</span>
        {'.'}
      </Trans>
    </p>
  );
};
