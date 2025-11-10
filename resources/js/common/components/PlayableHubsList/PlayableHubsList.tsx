import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuInfo } from 'react-icons/lu';
import { route } from 'ziggy-js';

import { GameAvatar } from '@/common/components/GameAvatar';
import { usePageProps } from '@/common/hooks/usePageProps';
import { cleanHubTitle } from '@/common/utils/cleanHubTitle';
import { cn } from '@/common/utils/cn';

import { BaseTooltip, BaseTooltipContent, BaseTooltipTrigger } from '../+vendor/BaseTooltip';

interface PlayableHubsListProps {
  hubs: App.Platform.Data.GameSet[];

  excludeHubIds?: number[];
  variant?: 'event' | 'game';
}

export const PlayableHubsList: FC<PlayableHubsListProps> = ({ hubs, excludeHubIds, variant }) => {
  const { can } = usePageProps<App.Platform.Data.GameShowPageProps>();

  const { t } = useTranslation();

  if (!hubs?.length) {
    return null;
  }

  const filteredHubs = filterHubs(hubs, {
    canManageGames: can.manageGames,
    excludeIds: excludeHubIds,
  });

  if (!filteredHubs.length) {
    return null;
  }

  const sortedHubs = filteredHubs.sort((a, b) => a.title!.localeCompare(b.title!));

  return (
    <div data-testid="hubs-list">
      <h2 className="mb-0 flex items-center gap-1.5 border-0 text-lg font-semibold">
        {variant === 'game' ? t('Additional Hubs') : t('Hubs')}

        {variant === 'game' ? (
          <BaseTooltip>
            <BaseTooltipTrigger>
              <LuInfo
                className={cn(
                  'hidden size-4 text-neutral-500 transition hover:text-neutral-300 sm:inline',
                  'light:text-neutral-700',
                )}
              />
            </BaseTooltipTrigger>

            <BaseTooltipContent className="max-w-72 font-normal leading-normal">
              <span className="text-xs font-normal">
                {t(
                  'Many game details above (like Developer and Genre) are also hubs. Click any to explore related games.',
                )}
              </span>
            </BaseTooltipContent>
          </BaseTooltip>
        ) : null}
      </h2>

      <div className="rounded-lg bg-embed p-1 light:border light:border-neutral-200 light:bg-white">
        <ul className="zebra-list overflow-hidden rounded-lg">
          {sortedHubs.map((hub) => (
            <li
              key={`hub-list-item-${hub.id}`}
              className="w-full p-2 first:rounded-t-lg last:rounded-b-lg"
            >
              <GameAvatar
                id={hub.id}
                title={cleanHubTitle(hub.title!)}
                dynamicTooltipType="hub"
                badgeUrl={hub.badgeUrl!}
                size={36}
                href={route('hub.show', { gameSet: hub })}
              />
            </li>
          ))}
        </ul>
      </div>
    </div>
  );
};

function filterHubs(
  allHubs: App.Platform.Data.GameSet[],
  options: Partial<{ canManageGames: boolean; excludeIds: number[] }>,
): App.Platform.Data.GameSet[] {
  const { canManageGames, excludeIds } = options;

  return allHubs.filter((hub) => {
    const isMetaHub = hub.title?.includes('Meta -') || hub.title?.includes('Meta|');
    const isEventHub = hub.isEventHub;
    const isAchievementExtras =
      hub.title?.includes('RANews -') || hub.title?.includes('Custom Awards -');
    const isRolloutSets = hub.title?.includes('Rollout Sets -');
    const isSeriesHub = hub.title?.includes('Series -') || hub.title?.includes('Subseries -');

    if (!isMetaHub && !isEventHub && !isAchievementExtras && !isRolloutSets && !isSeriesHub) {
      return false; // Everything else goes to metadata table.
    }

    // Only users who can manage games can see team meta hubs on the list.
    // Exception: Meta|Art hubs are publicly visible.
    if (hub.title?.includes('Meta|') && !hub.title?.includes('Meta|Art') && !canManageGames) {
      return false;
    }

    // There may be hub IDs marked for specific exclusion (such as those shown in game metadata).
    if (excludeIds?.includes(hub.id)) {
      return false;
    }

    return true;
  });
}
