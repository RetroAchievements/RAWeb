import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { useFormatDate } from '@/common/hooks/useFormatDate';
import { usePageProps } from '@/common/hooks/usePageProps';

import { OAuthConnectionRevocationDialogTrigger } from '../OAuthConnectionRevocationDialogTrigger';
import { SectionStandardCard } from '../SectionStandardCard';

export const ConnectedApplicationsSection: FC = () => {
  const { connectedOAuthApplications } = usePageProps<App.Community.Data.UserSettingsPageProps>();
  const { t } = useTranslation();

  const { formatDate } = useFormatDate();

  if (!connectedOAuthApplications.length) {
    return null;
  }

  return (
    <SectionStandardCard t_headingLabel={t('Connected Applications')}>
      <ul className="flex flex-col gap-3">
        {connectedOAuthApplications.map((application) => (
          <li
            key={application.clientId}
            className="flex flex-col justify-between gap-3 rounded border border-neutral-700 p-3 light:border-neutral-200 sm:flex-row sm:items-center"
          >
            <div>
              <p className="font-medium">{application.name}</p>
              <p className="text-sm text-neutral-400">
                {t('Connected {{date}}', {
                  date: formatDate(application.connectedAt, 'll'),
                })}
              </p>
            </div>

            <div className="self-center">
              <OAuthConnectionRevocationDialogTrigger application={application} />
            </div>
          </li>
        ))}
      </ul>
    </SectionStandardCard>
  );
};
