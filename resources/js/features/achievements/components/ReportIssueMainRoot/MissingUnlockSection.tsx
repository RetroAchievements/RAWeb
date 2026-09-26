import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { route } from 'ziggy-js';

import { usePageProps } from '@/common/hooks/usePageProps';
import { buildTrackingClassNames } from '@/common/utils/buildTrackingClassNames';

import { buildStructuredMessage } from './buildStructuredMessage';
import { ReportIssueOptionItem } from './ReportIssueOptionItem';
import { ReportIssueSection } from './ReportIssueSection';

const emulatorSupportDocsHref =
  'https://docs.retroachievements.org/general/emulator-support-and-issues.html';

export const MissingUnlockSection: FC = () => {
  const { achievement, hasCasualUnlockFromRestrictedClient } =
    usePageProps<App.Platform.Data.ReportAchievementIssuePageProps>();
  const { t } = useTranslation();

  const hasOnlyCasualUnlock = !!achievement.unlockedAt && !achievement.unlockedHardcoreAt;

  return (
    <ReportIssueSection
      t_heading={
        hasOnlyCasualUnlock
          ? t(
              'I earned this achievement in hardcore mode, but my profile shows only the casual unlock',
            )
          : t('I earned this achievement, but it is missing from my profile')
      }
    >
      {hasCasualUnlockFromRestrictedClient ? (
        <li className="flex w-full flex-col gap-1 rounded-sm bg-embed px-3 py-2">
          <p className="text-neutral-300 light:text-neutral-900">
            {t(
              'The emulator or core that recorded your unlock does not allow hardcore unlocks, so the site recorded it as casual. We cannot change it to hardcore. To earn hardcore unlocks, use a supported, up-to-date emulator or core.',
            )}
          </p>

          <a href={emulatorSupportDocsHref} target="_blank" rel="noreferrer">
            {t('Read about emulator support')}
          </a>
        </li>
      ) : (
        <ReportIssueOptionItem
          t_buttonText={t('Request Manual Unlock')}
          href={route('message-thread.create', {
            to: 'UnlockTeam',
            ...buildStructuredMessage(achievement, 'manual-unlock'),
          })}
          anchorClassName={buildTrackingClassNames('Click Request Manual Unlock')}
        >
          {t(
            'You need proof: a screenshot of the achievement popup, a video, or a later achievement.',
          )}
        </ReportIssueOptionItem>
      )}
    </ReportIssueSection>
  );
};
