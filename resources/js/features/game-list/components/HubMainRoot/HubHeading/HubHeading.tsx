import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuMessageCircle } from 'react-icons/lu';

import { baseButtonVariants } from '@/common/components/+vendor/BaseButton';
import { GameAvatar } from '@/common/components/GameAvatar';
import { InertiaLink } from '@/common/components/InertiaLink';
import { ManageButton } from '@/common/components/ManageButton';
import { usePageProps } from '@/common/hooks/usePageProps';
import { cleanHubTitle } from '@/common/utils/cleanHubTitle';

export const HubHeading: FC = () => {
  const { can, hub } = usePageProps<App.Platform.Data.HubPageProps>();

  const { t } = useTranslation();

  return (
    <div className="mb-3 flex w-full gap-x-3">
      {hub.badgeUrl ? (
        <div className="mb-2 inline self-end">
          <GameAvatar
            id={hub.id}
            title={cleanHubTitle(hub.title!)}
            badgeUrl={hub.badgeUrl}
            hasTooltip={false}
            shouldLink={false}
            showLabel={false}
            size={96}
          />
        </div>
      ) : null}

      <h1 className="text-h3 flex w-full flex-col justify-between gap-2 self-end sm:mt-2.5 sm:!text-[2.0em] md:flex-row md:items-center">
        <div className="flex items-center gap-2">
          <img aria-hidden={true} src="/assets/images/system/hubs.png" className="size-6" />
          {cleanHubTitle(hub.title!)}
        </div>

        {can.manageGameSets || hub.forumTopicId ? (
          <div className="flex gap-2">
            {hub.forumTopicId ? (
              <InertiaLink
                href={route('forum-topic.show', { topic: hub.forumTopicId })}
                className={baseButtonVariants({
                  size: 'sm',
                  className: 'gap-1',
                })}
              >
                <LuMessageCircle className="size-4" />
                {t('View Forum Topic')}
              </InertiaLink>
            ) : null}

            {can.manageGameSets ? <ManageButton href={`/manage/hubs/${hub.id}`} /> : null}
          </div>
        ) : null}
      </h1>
    </div>
  );
};
