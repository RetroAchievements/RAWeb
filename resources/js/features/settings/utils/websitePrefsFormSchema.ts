import { z } from 'zod';

import { StringifiedUserPreference } from '@/common/utils/generatedAppConstants';

export const websitePrefsFormSchema = z.object({
  [StringifiedUserPreference.EmailOn_ActivityComment]: z.boolean(),
  [StringifiedUserPreference.EmailOn_AchievementComment]: z.boolean(),
  [StringifiedUserPreference.EmailOn_UserWallComment]: z.boolean(),
  [StringifiedUserPreference.EmailOn_ForumReply]: z.boolean(),
  [StringifiedUserPreference.EmailOn_Followed]: z.boolean(),
  [StringifiedUserPreference.EmailOn_PrivateMessage]: z.boolean(),
  [StringifiedUserPreference.EmailOn_Newsletter]: z.boolean(),
  [StringifiedUserPreference.Site_SuppressMatureContentWarning]: z.boolean(),
  [StringifiedUserPreference.SiteMsgOn_ActivityComment]: z.boolean(),
  [StringifiedUserPreference.SiteMsgOn_AchievementComment]: z.boolean(),
  [StringifiedUserPreference.SiteMsgOn_UserWallComment]: z.boolean(),
  [StringifiedUserPreference.SiteMsgOn_ForumReply]: z.boolean(),
  [StringifiedUserPreference.SiteMsgOn_Followed]: z.boolean(),
  [StringifiedUserPreference.SiteMsgOn_PrivateMessage]: z.boolean(),
  [StringifiedUserPreference.SiteMsgOn_Newsletter]: z.boolean(),
  [StringifiedUserPreference.Forum_ShowAbsoluteDates]: z.boolean(),
  [StringifiedUserPreference.Game_HideMissableIndicators]: z.boolean(),
  [StringifiedUserPreference.User_OnlyContactFromFollowing]: z.boolean(),
});
