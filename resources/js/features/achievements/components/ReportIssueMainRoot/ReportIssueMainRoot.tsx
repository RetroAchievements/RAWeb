import type { FC } from 'react';

import { buildTrackingClassNames } from '@/common/utils/buildTrackingClassNames';
import { usePageProps } from '@/features/settings/hooks/usePageProps';

import { AchievementBreadcrumbs } from '../AchievementBreadcrumbs';
import { AchievementHeading } from '../AchievementHeading';
import { buildStructuredMessage } from './buildStructuredMessage';
import { ReportIssueOptionItem } from './ReportIssueOptionItem';
import { SessionDrivenIssueListItems } from './SessionDrivenIssueListItems';
import { UnlockStatusLabel } from './UnlockStatusLabel';

export const ReportIssueMainRoot: FC = () => {
  const { achievement } = usePageProps<App.Platform.Data.ReportAchievementIssuePageProps>();

  return (
    <div>
      <AchievementBreadcrumbs
        currentPageLabel="Report Issue"
        system={achievement.game?.system}
        game={achievement.game}
        achievement={achievement}
      />
      <AchievementHeading achievement={achievement}>
        {achievement.title} - Report Issue
      </AchievementHeading>

      <div className="mb-3">
        <UnlockStatusLabel />
      </div>

      <p className="mb-2">What sort of issue would you like to report?</p>

      <ul className="flex flex-col gap-5 sm:gap-3">
        <SessionDrivenIssueListItems />

        <ReportIssueOptionItem
          buttonText="Report to DevCompliance"
          href={route('message.create', {
            to: 'DevCompliance',
            ...buildStructuredMessage(achievement, 'unwelcome-concept'),
          })}
          anchorClassName={buildTrackingClassNames('Click Report Unwelcome Concept')}
        >
          The achievement contains an{' '}
          <a
            href="https://docs.retroachievements.org/guidelines/content/unwelcome-concepts.html"
            target="_blank"
            className={buildTrackingClassNames('Click Unwelcome Concept Docs Link')}
          >
            unwelcome concept
          </a>
          .
        </ReportIssueOptionItem>

        <ReportIssueOptionItem
          buttonText="Report to QATeam"
          href={route('message.create', {
            to: 'QATeam',
            ...buildStructuredMessage(achievement, 'misclassification'),
          })}
          anchorClassName={buildTrackingClassNames('Click Report Misclassification')}
        >
          The achievement type (progression/win/missable) is not correct.
        </ReportIssueOptionItem>

        <ReportIssueOptionItem
          buttonText="Report to WritingTeam"
          href={route('message.create', {
            to: 'WritingTeam',
            ...buildStructuredMessage(achievement, 'writing-error'),
          })}
        >
          There is a spelling or grammatical error in the title or description.
        </ReportIssueOptionItem>

        <ReportIssueOptionItem
          buttonText="Message QATeam"
          href={route('message.create', {
            to: 'QATeam',
            ...buildStructuredMessage(achievement, 'achievement-issue'),
          })}
        >
          I have an issue with this achievement that is not described above.
        </ReportIssueOptionItem>
      </ul>
    </div>
  );
};
