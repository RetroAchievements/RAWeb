import { useLaravelReactI18n } from 'laravel-react-i18n';
import type { FC } from 'react';

import { usePageProps } from '@/common/hooks/usePageProps';

// Other elements on the page contain some of the same labels, so we
// need to target specifically by this <p> tag.
export const testId = 'unlock-status-label';

export const UnlockStatusLabel: FC = () => {
  const { achievement, hasSession } =
    usePageProps<App.Platform.Data.ReportAchievementIssuePageProps>();

  const { t } = useLaravelReactI18n();

  // Don't show any label if the user has never loaded the game.
  if (!hasSession) {
    return null;
  }

  if (!achievement.unlockedAt && !achievement.unlockedHardcoreAt) {
    return (
      <p data-testid={testId}>
        {t('You')} <span className="font-bold">{t('have not')}</span>{' '}
        {t('unlocked this achievement.')}
      </p>
    );
  }

  if (achievement.unlockedHardcoreAt) {
    return (
      <p data-testid={testId}>
        {t('You')} <span className="font-bold">{t('have')}</span> {t('unlocked this achievement.')}
      </p>
    );
  }

  return (
    <p data-testid={testId}>
      {t('You')} <span className="font-bold">{t('have')}</span> {t('unlocked this achievement')}{' '}
      <span className="font-bold">{t('in softcore')}</span>
      {t('.')}
    </p>
  );
};
