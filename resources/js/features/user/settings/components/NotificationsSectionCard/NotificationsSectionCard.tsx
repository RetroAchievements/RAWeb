import type { FC } from 'react';

import { StringifiedUserPreference } from '@/common/utils/generatedAppConstants';

import { SectionFormCard } from '../SectionFormCard';
import { NotificationsSmallRow } from './NotificationsSmallRow';
import { NotificationsTableRow } from './NotificationsTableRow';
import { useNotificationsSectionForm } from './useNotificationsSectionForm';

const notificationSettings = [
  {
    label: 'Comments on my activity',
    emailFieldName: StringifiedUserPreference.EmailOn_ActivityComment,
    siteFieldName: StringifiedUserPreference.SiteMsgOn_ActivityComment,
  },
  {
    label: 'Comments on an achievement I created',
    emailFieldName: StringifiedUserPreference.EmailOn_AchievementComment,
    siteFieldName: StringifiedUserPreference.SiteMsgOn_AchievementComment,
  },
  {
    label: 'Comments on my user wall',
    emailFieldName: StringifiedUserPreference.EmailOn_UserWallComment,
    siteFieldName: StringifiedUserPreference.SiteMsgOn_UserWallComment,
  },
  {
    label: "Comments on a forum topic I'm involved in",
    emailFieldName: StringifiedUserPreference.EmailOn_ForumReply,
    siteFieldName: StringifiedUserPreference.SiteMsgOn_ForumReply,
  },
  {
    label: 'Someone follows me',
    emailFieldName: StringifiedUserPreference.EmailOn_Followed,
    siteFieldName: StringifiedUserPreference.SiteMsgOn_Followed,
  },
  {
    label: 'I receive a private message',
    emailFieldName: StringifiedUserPreference.EmailOn_PrivateMessage,
  },
];

interface NotificationsSectionCardProps {
  currentWebsitePrefs: number;
  onUpdateWebsitePrefs: (newWebsitePrefs: number) => unknown;
}

export const NotificationsSectionCard: FC<NotificationsSectionCardProps> = ({
  currentWebsitePrefs,
  onUpdateWebsitePrefs,
}) => {
  const { form, mutation, onSubmit } = useNotificationsSectionForm(
    currentWebsitePrefs,
    onUpdateWebsitePrefs,
  );

  return (
    <SectionFormCard
      headingLabel="Notifications"
      formMethods={form}
      onSubmit={onSubmit}
      isSubmitting={mutation.isPending}
    >
      <div className="@container">
        <div className="flex flex-col gap-5 @xl:hidden">
          {notificationSettings.map((setting) => (
            <NotificationsSmallRow
              key={setting.label}
              label={setting.label}
              emailFieldName={setting.emailFieldName}
              siteFieldName={setting.siteFieldName}
            />
          ))}
        </div>

        <table className="hidden @xl:table">
          <thead className="sr-only">
            <tr>
              <th scope="col">Notification type</th>
              <th scope="col">Email notifications</th>
              <th scope="col">Site notifications</th>
            </tr>
          </thead>

          <tbody className="[&>tr>td]:!px-0 [&>tr>td]:py-2 [&>tr>th]:!px-0 [&>tr]:!bg-embed">
            {notificationSettings.map((setting) => (
              <NotificationsTableRow
                key={setting.label}
                label={setting.label}
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
