import type { FC } from 'react';
import { Trans, useTranslation } from 'react-i18next';
import { route } from 'ziggy-js';

import {
  BaseAlertDialog,
  BaseAlertDialogAction,
  BaseAlertDialogCancel,
  BaseAlertDialogContent,
  BaseAlertDialogDescription,
  BaseAlertDialogFooter,
  BaseAlertDialogTitle,
  BaseAlertDialogTrigger,
} from '@/common/components/+vendor/BaseAlertDialog';
import { BaseButton } from '@/common/components/+vendor/BaseButton';
import { buildTrackingClassNames } from '@/common/utils/buildTrackingClassNames';

import { buildStructuredMessage } from '../buildStructuredMessage';
import { ReportIssueOptionItem } from '../ReportIssueOptionItem';

interface ReportToDeveloperComplianceListItemProps {
  achievement: App.Platform.Data.Achievement;
}

export const ReportToDeveloperComplianceListItem: FC<ReportToDeveloperComplianceListItemProps> = ({
  achievement,
}) => {
  const { t } = useTranslation();

  const destinationRoute = route('message-thread.create', {
    to: 'DevCompliance',
    ...buildStructuredMessage(achievement, 'unwelcome-concept'),
  });

  const handleSubsetContinueClick = () => {
    window.location.assign(destinationRoute);
  };

  return (
    <>
      {achievement.game?.isSubsetGame ? (
        <li className="flex w-full flex-col items-center justify-between gap-2 rounded bg-embed px-3 py-2 sm:flex-row">
          <p>
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
          </p>

          <div className="self-end sm:self-auto">
            <BaseAlertDialog>
              <BaseAlertDialogTrigger asChild>
                <BaseButton size="sm">{t('Report to DevCompliance')}</BaseButton>
              </BaseAlertDialogTrigger>

              <BaseAlertDialogContent>
                <BaseAlertDialogTitle>{t('Are you sure?')}</BaseAlertDialogTitle>

                <BaseAlertDialogDescription className="flex flex-col gap-4">
                  <span>
                    {t(
                      "This achievement appears to be part of a subset, which is allowed to contain concepts that might otherwise be unwelcome in the game's main set.",
                    )}
                  </span>

                  <span>
                    {t(
                      'Subsets are designed to be optional content that players can choose to engage with or avoid based on their preferences.',
                    )}
                  </span>

                  <span>
                    {t(
                      'Do you still want to report this achievement as containing an unwelcome concept?',
                    )}
                  </span>
                </BaseAlertDialogDescription>

                <BaseAlertDialogFooter>
                  <BaseAlertDialogCancel>{t('Nevermind')}</BaseAlertDialogCancel>

                  <BaseAlertDialogAction onClick={handleSubsetContinueClick}>
                    {t('Continue')}
                  </BaseAlertDialogAction>
                </BaseAlertDialogFooter>
              </BaseAlertDialogContent>
            </BaseAlertDialog>
          </div>
        </li>
      ) : (
        <ReportIssueOptionItem
          t_buttonText={t('Report to DevCompliance')}
          href={destinationRoute}
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
      )}
    </>
  );
};
