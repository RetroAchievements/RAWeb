import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { usePageProps } from '@/common/hooks/usePageProps';

import { OAuthApplicationDeactivationDialogTrigger } from '../OAuthApplicationDeactivationDialogTrigger';
import { OAuthApplicationManagementDialogTrigger } from '../OAuthApplicationManagementDialogTrigger';
import { OAuthRegistrationDialogTrigger } from '../OAuthRegistrationDialogTrigger';
import { SectionStandardCard } from '../SectionStandardCard';

export const YourApplicationsSection: FC = () => {
  const { auth, can, oauthApplicationLimit, oauthApplications } =
    usePageProps<App.Community.Data.UserSettingsPageProps>();
  const { t } = useTranslation();

  /**
   * The quota is a validation rule rather than a permission, so `createOAuthClients`
   * stays true for a user who is already at their limit.
   */
  const hasReachedApplicationLimit = oauthApplications.length >= oauthApplicationLimit;

  return (
    <SectionStandardCard t_headingLabel={t('For Developers')}>
      <div className="flex flex-col gap-4">
        {oauthApplications.length ? (
          <ul className="flex flex-col gap-3">
            {oauthApplications.map((application) => (
              <li
                key={application.id}
                className="flex items-center justify-between gap-4 rounded border border-neutral-700 p-3 light:border-neutral-200"
              >
                <div className="min-w-0">
                  <p className="font-medium">{application.name}</p>
                  <code className="block truncate text-xs text-neutral-400">{application.id}</code>
                </div>

                <div className="flex shrink-0 items-center gap-2">
                  <OAuthApplicationManagementDialogTrigger application={application} />
                  <OAuthApplicationDeactivationDialogTrigger application={application} />
                </div>
              </li>
            ))}
          </ul>
        ) : (
          <p>{t('Build and manage OAuth applications for the RetroAchievements community.')}</p>
        )}

        {hasReachedApplicationLimit ? (
          <p className="text-sm text-neutral-400">
            {t('You can register up to {{count}} application', {
              count: oauthApplicationLimit,
            })}
          </p>
        ) : null}

        {/*
          This section only renders while OAuth is enabled, so the create ability can
          only be withheld by an unverified email address or a fresh account.
        */}
        {can.createOAuthClients ? (
          <OAuthRegistrationDialogTrigger disabled={hasReachedApplicationLimit} />
        ) : (
          <p className="text-sm text-neutral-400">
            {auth?.user.isEmailVerified
              ? t('Your account is too new to register an application.')
              : t('Verify your email address to register an application.')}
          </p>
        )}
      </div>
    </SectionStandardCard>
  );
};
