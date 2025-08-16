import type { FC } from 'react';
import { Trans, useTranslation } from 'react-i18next';

export const UnresolvedTicketsWarning: FC = () => {
  const { t } = useTranslation();

  return (
    <span className="font-bold">
      {' '}
      {t('Please ensure any open tickets have been addressed before making this claim.')}
    </span>
  );
};

export const RevisionPlanWarning: FC = () => {
  return (
    <span>
      {' '}
      <Trans
        i18nKey="Please only create this claim if a <1>revision plan</1> has been posted and approved."
        components={{
          1: (
            // eslint-disable-next-line jsx-a11y/anchor-has-content -- this is fine in Trans components
            <a
              href="https://docs.retroachievements.org/guidelines/content/achievement-set-revisions.html"
              target="_blank"
            />
          ),
        }}
      />
    </span>
  );
};

export const SubsetApprovalWarning: FC = () => {
  return (
    <span>
      {' '}
      <Trans
        i18nKey="Please only create this claim if the subset has been <1>approved</1>."
        components={{
          1: (
            // eslint-disable-next-line jsx-a11y/anchor-has-content -- this is fine in Trans components
            <a
              href="https://docs.retroachievements.org/guidelines/content/subsets.html#approval-and-claims"
              target="_blank"
            />
          ),
        }}
      />
    </span>
  );
};

export const ForumTopicNotice: FC = () => {
  const { t } = useTranslation();

  return <span> {t('An official forum topic for the game will also be created.')}</span>;
};

export const ProgressReportWarning: FC = () => {
  return (
    <span>
      {' '}
      <Trans
        i18nKey="<1>Post a progress report in the game's forum topic before extending,</1> <2>otherwise your claim may be dropped.</2>"
        components={{
          1: <span className="font-bold" />,
          2: <span />,
        }}
      />
    </span>
  );
};

interface QuickCompletionWarningProps {
  minutesActive?: number;
}

export const QuickCompletionWarning: FC<QuickCompletionWarningProps> = ({ minutesActive }) => {
  const { t } = useTranslation();

  if (!minutesActive || minutesActive > 1440) {
    return null;
  }

  return (
    <span className="font-bold">
      {' '}
      {t(
        'Please ensure you have approval to complete this claim within 24 hours of the claim being made.',
      )}
    </span>
  );
};
