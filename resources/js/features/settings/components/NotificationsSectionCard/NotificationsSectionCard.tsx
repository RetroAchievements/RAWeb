import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { StringifiedUserPreference } from '@/common/utils/generatedAppConstants';

import type { UserPreferenceValue } from '../../models';
import { SectionFormCard } from '../SectionFormCard';
import { NotificationsSmallRow } from './NotificationsSmallRow';
import { NotificationsTableRow } from './NotificationsTableRow';
import { useNotificationsSectionForm } from './useNotificationsSectionForm';

interface NotificationsSectionCardProps {
  currentWebsitePrefs: number;
  onUpdateWebsitePrefs: (newWebsitePrefs: number) => unknown;
}

export const NotificationsSectionCard: FC<NotificationsSectionCardProps> = ({
  currentWebsitePrefs,
  onUpdateWebsitePrefs,
}) => {
  const { t } = useTranslation();

  const { form, mutation, onSubmit } = useNotificationsSectionForm(
    currentWebsitePrefs,
    onUpdateWebsitePrefs,
  );

  const notificationSettings = useNotificationSettings();

  return (
    <SectionFormCard
      t_headingLabel={t('Notifications')}
      formMethods={form}
      onSubmit={onSubmit}
      isSubmitting={mutation.isPending}
    >
      <div className="@container">
        <div className="flex flex-col gap-5 @xl:hidden">
          {notificationSettings.map((setting) => (
            <NotificationsSmallRow
              key={setting.t_label}
              t_label={setting.t_label}
              emailFieldName={setting.emailFieldName}
              siteFieldName={setting.siteFieldName}
            />
          ))}
        </div>

        <table className="hidden @xl:table">
          <thead className="sr-only">
            <tr>
              <th scope="col">{t('Notification type')}</th>
              <th scope="col">{t('Email notifications')}</th>
              <th scope="col">{t('Site notifications')}</th>
            </tr>
          </thead>

          <tbody className="[&>tr>td]:!px-0 [&>tr>td]:py-2 [&>tr>th]:!px-0 [&>tr]:!bg-embed">
            {notificationSettings.map((setting) => (
              <NotificationsTableRow
                key={setting.t_label}
                t_label={setting.t_label}
                emailFieldName={setting.emailFieldName}
                siteFieldName={setting.siteFieldName}
              />
            ))}
          </tbody>
        </table>
      </div>
    </SectionFormCard>
  );
};

function useNotificationSettings() {
  const { t } = useTranslation();

  const notificationSettings: Array<{
    t_label: string;
    emailFieldName?: UserPreferenceValue;
    siteFieldName?: UserPreferenceValue;
  }> = [
    {
      t_label: t('Comments on my activity'),
      emailFieldName: StringifiedUserPreference.EmailOn_ActivityComment,
      siteFieldName: StringifiedUserPreference.SiteMsgOn_ActivityComment,
    },
    {
      t_label: t('Comments on an achievement I created'),
      emailFieldName: StringifiedUserPreference.EmailOn_AchievementComment,
      siteFieldName: StringifiedUserPreference.SiteMsgOn_AchievementComment,
    },
    {
      t_label: t('Comments on my user wall'),
      emailFieldName: StringifiedUserPreference.EmailOn_UserWallComment,
      siteFieldName: StringifiedUserPreference.SiteMsgOn_UserWallComment,
    },
    {
      t_label: t("Comments on a forum topic I'm involved in"),
      emailFieldName: StringifiedUserPreference.EmailOn_ForumReply,
      siteFieldName: StringifiedUserPreference.SiteMsgOn_ForumReply,
    },
    {
      t_label: t('Someone follows me'),
      emailFieldName: StringifiedUserPreference.EmailOn_Followed,
      siteFieldName: StringifiedUserPreference.SiteMsgOn_Followed,
    },
    {
      t_label: t('I receive a private message'),
      emailFieldName: StringifiedUserPreference.EmailOn_PrivateMessage,
    },
    {
      t_label: t('Ticket activity'),
      emailFieldName: StringifiedUserPreference.EmailOn_TicketActivity,
    },
  ];

  return notificationSettings;
}
