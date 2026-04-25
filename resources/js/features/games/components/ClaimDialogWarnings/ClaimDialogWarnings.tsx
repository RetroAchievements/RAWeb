import type { FC } from 'react';
import { Trans, useTranslation } from 'react-i18next';

export const UnresolvedTicketsWarning: FC = () => {
  return (
    <span>
      <Trans
        i18nKey="Claims should not be made while you have an <1>unaddressed ticket</1>."
        components={{
          1: (
            // eslint-disable-next-line jsx-a11y/anchor-has-content -- this is fine in Trans components
            <a
              href="https://docs.retroachievements.org/guidelines/developers/claims-system.html#claims-system-guidelines"
              target="_blank"
            />
          ),
        }}
      />
    </span>
  );
};

export const RevisionPlanWarning: FC = () => {
  return (
    <span>
      <Trans
        i18nKey="An approved <1>revision plan</1> is required for this claim."
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
      <Trans
        i18nKey="<1>Subset approval</1> is required for this claim."
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

  return <span>{t('An official forum topic will be created for this game.')}</span>;
};

export const ProgressReportWarning: FC = () => {
  return (
    <span>
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

export const QuickCompletionWarning: FC = () => {
  const { t } = useTranslation();

  return (
    <span className="font-bold">
      {t(
        'Please ensure you have approval to complete this claim within 24 hours of the claim being made.',
      )}
    </span>
  );
};
