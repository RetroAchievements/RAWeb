import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { usePageProps } from '@/common/hooks/usePageProps';
import { StringifiedUserPreference } from '@/common/utils/generatedAppConstants';
import type { TranslatedString } from '@/types/i18next';

import type { UserPreferenceValue } from '../../models';
import { SectionFormCard } from '../SectionFormCard';
import { NotificationsSmallRow } from './NotificationsSmallRow';
import { NotificationsTableRow } from './NotificationsTableRow';
import { useNotificationsSectionForm } from './useNotificationsSectionForm';

interface NotificationsSectionCardProps {
  currentWebsitePrefs: number;
}

export const NotificationsSectionCard: FC<NotificationsSectionCardProps> = ({
  currentWebsitePrefs,
}) => {
  const { t } = useTranslation();

  const { form, mutation, onSubmit } = useNotificationsSectionForm(currentWebsitePrefs);

  const notificationSettings = useNotificationSettings();

  const visibleSettings = notificationSettings.filter((setting) => setting.canShow !== false);

  return (
    <SectionFormCard
      t_headingLabel={t('Notifications')}
      formMethods={form}
      onSubmit={onSubmit}
      isSubmitting={mutation.isPending}
    >
      <div className="flex flex-col gap-4">
        <p>{t('Commenting on any wall or forum topic will automatically subscribe you to it.')}</p>

        <div className="@container">
          <div className="flex flex-col gap-5 @xl:hidden">
            {visibleSettings.map((setting) => (
              <NotificationsSmallRow
                key={setting.t_label}
                t_label={setting.t_label}
                emailFieldName={setting.emailFieldName}
              />
            ))}
          </div>

          <table className="hidden @xl:table">
            <thead className="sr-only">
              <tr>
                <th scope="col">{t('Notification type')}</th>
                <th scope="col">{t('Email notifications')}</th>
              </tr>
            </thead>

            <tbody className="[&>tr>td]:!px-0 [&>tr>td]:py-2 [&>tr>th]:!px-0 [&>tr]:!bg-embed">
              {visibleSettings.map((setting) => (
                <NotificationsTableRow
                  key={setting.t_label}
                  t_label={setting.t_label}
                  emailFieldName={setting.emailFieldName}
                />
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </SectionFormCard>
  );
};

function useNotificationSettings() {
  const { auth } = usePageProps();
  const { t } = useTranslation();

  const notificationSettings: Array<{
    t_label: TranslatedString;
    emailFieldName?: UserPreferenceValue;
    canShow?: boolean;
  }> = [
    {
      t_label: t(
        "Someone comments on any achievement in games where I've subscribed to all achievement comments",
      ),
      emailFieldName: StringifiedUserPreference.EmailOn_ActivityComment,
      canShow:
        auth?.user.roles.includes('developer') || auth?.user.roles.includes('developer-junior'),
    },
    {
      t_label: t("Someone comments on game or achievement walls I'm subscribed to"),
      emailFieldName: StringifiedUserPreference.EmailOn_AchievementComment,
    },
    {
      t_label: t("Someone comments on user walls I'm subscribed to"),
      emailFieldName: StringifiedUserPreference.EmailOn_UserWallComment,
    },
    {
      t_label: t("Someone posts in forum topics I'm subscribed to"),
      emailFieldName: StringifiedUserPreference.EmailOn_ForumReply,
    },
    {
      t_label: t('Someone follows me'),
      emailFieldName: StringifiedUserPreference.EmailOn_Followed,
    },
    {
      t_label: t('I receive a private message'),
      emailFieldName: StringifiedUserPreference.EmailOn_PrivateMessage,
    },
    {
      t_label: t("There's activity on a ticket I'm associated with"),
      emailFieldName: StringifiedUserPreference.EmailOn_TicketActivity,
    },
  ];

  return notificationSettings;
}
