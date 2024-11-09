import type { FC } from 'react';
import { Trans } from 'react-i18next';

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
        <Trans
          i18nKey="You <1>have not</1> unlocked this achievement."
          components={{
            1: <HaveNotSpan />,
          }}
        >
          {'You '}
          <HaveNotSpan />
          {' unlocked this achievement.'}
        </Trans>
      </p>
    );
  }

  if (achievement.unlockedHardcoreAt) {
    return (
      <p data-testid={testId}>
        <Trans
          i18nKey="You <1>have</1> unlocked this achievement."
          components={{ 1: <HaveSpan /> }}
        >
          {'You '}
          <HaveSpan />
          {' unlocked this achievement.'}
        </Trans>
      </p>
    );
  }

  return (
    <p data-testid={testId}>
      <Trans
        i18nKey="You <1>have</1> unlocked this achievement <2>in softcore</2>."
        components={{ 1: <HaveSpan />, 2: <InSoftcoreSpan /> }}
      >
        {'You '}
        <HaveSpan />
        {' unlocked this achievement '}
        <InSoftcoreSpan />
        {'.'}
      </Trans>
    </p>
  );
};

const HaveNotSpan = () => <span className="font-bold">{'have not'}</span>;
const HaveSpan = () => <span className="font-bold">{'have'}</span>;
const InSoftcoreSpan = () => <span className="font-bold">{'in softcore'}</span>;
