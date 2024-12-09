import type { FC } from 'react';

import { GameAvatar } from '@/common/components/GameAvatar';
import { usePageProps } from '@/common/hooks/usePageProps';
import { cleanHubTitle } from '@/common/utils/cleanHubTitle';

// TODO keep in mind, probably have to double write back to the hub game for the time being
// TODO audit log (fix audit log comments so they're actually being recorded)
// TODO change hub title
// TODO add,remove games
// TODO add,remove hub links
// TODO show,edit internal notes----------- drop internal notes & command
// TODO comments --- maybe internal notes should just be a comment...
// TODO badge image upload

export const HubHeading: FC = () => {
  const { hub } = usePageProps<App.Platform.Data.HubPageProps>();

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

        {/* {can.manageGameSets ? (
          <Drawer.Root direction="right" handleOnly={true}>
            <Drawer.Trigger asChild>
              <BaseButton size="sm">{t('Manage')}</BaseButton>
            </Drawer.Trigger>
            <Drawer.Portal>
              <Drawer.Overlay className="fixed inset-0 z-20 bg-black/60" />
              <Drawer.Content
                className="fixed bottom-2 right-2 top-2 z-50 flex w-[310px] outline-none"
                // The gap between the edge of the screen and the drawer is 8px in this case.
                style={{ '--initial-transform': 'calc(100% + 8px)' } as React.CSSProperties}
              >
                <div className="flex h-full w-full grow select-text flex-col rounded-[16px] border border-embed-highlight bg-embed p-5">
                  <div className="mx-auto max-w-md">
                    <Drawer.Title className="mb-2 font-medium">
                      It supports all directions.
                    </Drawer.Title>
                    <Drawer.Description className="mb-2">
                      This one specifically is not touching the edge of the screen, but that&apos;s
                      not required for a side drawer.
                    </Drawer.Description>
                  </div>
                </div>
              </Drawer.Content>
            </Drawer.Portal>
          </Drawer.Root>
        ) : null} */}
      </h1>
    </div>
  );
};
