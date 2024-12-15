import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuExternalLink } from 'react-icons/lu';

import { baseButtonVariants } from '@/common/components/+vendor/BaseButton';
import { GameAvatar } from '@/common/components/GameAvatar';
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

      <h1 className="text-h3 flex w-full items-center justify-between gap-2 self-end sm:mt-2.5 sm:!text-[2.0em]">
        <div className="flex items-center gap-2">
          <img aria-hidden={true} src="/assets/images/system/hubs.png" className="size-6" />
          {cleanHubTitle(hub.title!)}
        </div>

        {can.manageGameSets ? (
          // Filament named routes are excluded from the front-end type mappings for performance reasons.
          <a
            href={`/manage/hubs/${hub.id}`}
            className={baseButtonVariants({
              size: 'sm',
              className: 'gap-1',
            })}
            target="_blank"
          >
            {t('Manage')}
            <LuExternalLink className="size-4" />
          </a>
        ) : null}
      </h1>
    </div>
  );
};
