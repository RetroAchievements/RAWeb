import type { FC } from 'react';
import { Trans, useTranslation } from 'react-i18next';

import { AchievementBreadcrumbs } from '@/common/components/AchievementBreadcrumbs';
import { AchievementHeading } from '@/common/components/AchievementHeading';
import { usePageProps } from '@/common/hooks/usePageProps';
import { buildTrackingClassNames } from '@/common/utils/buildTrackingClassNames';

import { buildStructuredMessage } from './buildStructuredMessage';
import { ReportIssueOptionItem } from './ReportIssueOptionItem';
import { SessionDrivenIssueListItems } from './SessionDrivenIssueListItems';
import { UnlockStatusLabel } from './UnlockStatusLabel';

export const ReportIssueMainRoot: FC = () => {
  const { achievement } = usePageProps<App.Platform.Data.ReportAchievementIssuePageProps>();

  const { t } = useTranslation();

  return (
    <div>
      <AchievementBreadcrumbs
        t_currentPageLabel={t('Report Issue')}
        system={achievement.game?.system}
        game={achievement.game}
        achievement={achievement}
      />
      <AchievementHeading achievement={achievement}>
        {t('{{achievementTitle}} - Report Issue', { achievementTitle: achievement.title })}
      </AchievementHeading>

      <div className="mb-3">
        <UnlockStatusLabel />
      </div>

      <p className="mb-2">{t('What sort of issue would you like to report?')}</p>

      <ul className="flex flex-col gap-5 sm:gap-3">
        <SessionDrivenIssueListItems />

        <ReportIssueOptionItem
          t_buttonText={t('Report to DevCompliance')}
          href={route('message.create', {
            to: 'DevCompliance',
            ...buildStructuredMessage(achievement, 'unwelcome-concept'),
          })}
          anchorClassName={buildTrackingClassNames('Click Report Unwelcome Concept')}
        >
          <Trans
            i18nKey="The achievement contains an <1>unwelcome concept</1>."
            components={{
              1: (
                <a
                  href="https://docs.retroachievements.org/guidelines/content/unwelcome-concepts.html"
                  target="_blank"
                  className={buildTrackingClassNames('Click Unwelcome Concept Docs Link')}
                />
              ),
            }}
          />
        </ReportIssueOptionItem>

        <ReportIssueOptionItem
          t_buttonText={t('Report to QATeam')}
          href={route('message.create', {
            to: 'QATeam',
            ...buildStructuredMessage(achievement, 'misclassification'),
          })}
          anchorClassName={buildTrackingClassNames('Click Report Misclassification')}
        >
          {t('The achievement type (progression/win/missable) is not correct.')}
        </ReportIssueOptionItem>

        <ReportIssueOptionItem
          t_buttonText={t('Report to WritingTeam')}
          href={route('message.create', {
            to: 'WritingTeam',
            ...buildStructuredMessage(achievement, 'writing-error'),
          })}
        >
          {t('There is a spelling or grammatical error in the title or description.')}
        </ReportIssueOptionItem>

        <ReportIssueOptionItem
          t_buttonText={t('Message QATeam')}
          href={route('message.create', {
            to: 'QATeam',
            ...buildStructuredMessage(achievement, 'achievement-issue'),
          })}
        >
          {t('I have an issue with this achievement that is not described above.')}
        </ReportIssueOptionItem>
      </ul>
    </div>
  );
};
