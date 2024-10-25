import { useLaravelReactI18n } from 'laravel-react-i18n';
import type { FC } from 'react';

import { UserAvatar } from '@/common/components/UserAvatar';

import { HomeHeading } from '../../HomeHeading';
import { SeeMoreLink } from '../../SeeMoreLink';

export const RecentForumPosts: FC = () => {
  const { t } = useLaravelReactI18n();

  return (
    <div>
      <HomeHeading>{t('Forum Recent Posts')}</HomeHeading>

      <div className="flex flex-col gap-y-1">
        <RecentForumPostItem />
        <RecentForumPostItem />
        <RecentForumPostItem />
        <RecentForumPostItem />
      </div>

      <SeeMoreLink href={route('forum.recent-posts')} asClientSideRoute={true} />
    </div>
  );
};

const RecentForumPostItem: FC = () => {
  const { t } = useLaravelReactI18n();

  return (
    <div className="rounded bg-embed px-2.5 py-1.5">
      <div className="flex items-center justify-between">
        <div className="flex items-center gap-1.5">
          <UserAvatar {...mockUser} size={16} />
          <span className="smalldate">{'1 week ago'}</span>
        </div>

        <a href="#">{t('View')}</a>
      </div>

      <p>
        {t('in')} <a href="#">{'RoadKill'}</a>
      </p>

      <p className="text-overflow-wrap">
        {
          "That's a pretty much complete list only things I can really think of would be achievements for killi..."
        }
      </p>
    </div>
  );
};

const mockUser: App.Data.User = {
  id: 1,
  displayName: 'Scott',
  avatarUrl: 'http://media.retroachievements.org/UserPic/Scott.png',
  isMuted: false,
  mutedUntil: null,
};
