import type { FC } from 'react';

import { usePageProps } from '@/features/settings/hooks/usePageProps';

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
        You <span className="font-bold">have not</span> unlocked this achievement.
      </p>
    );
  }

  if (achievement.unlockedHardcoreAt) {
    return (
      <p data-testid={testId}>
        You <span className="font-bold">have</span> unlocked this achievement.
      </p>
    );
  }

  return (
    <p data-testid={testId}>
      You <span className="font-bold">have</span> unlocked this achievement{' '}
      <span className="font-bold">in softcore</span>.
    </p>
  );
};
