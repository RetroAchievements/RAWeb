import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { route } from 'ziggy-js';

import { GameAvatar } from '@/common/components/GameAvatar';
import { usePageProps } from '@/common/hooks/usePageProps';
import { cleanHubTitle } from '@/common/utils/cleanHubTitle';

interface PlayableHubsListProps {
  hubs: App.Platform.Data.GameSet[];

  excludeHubIds?: number[];
}

export const PlayableHubsList: FC<PlayableHubsListProps> = ({ hubs, excludeHubIds }) => {
  const { can } = usePageProps<App.Platform.Data.GameShowPageProps>();

  const { t } = useTranslation();

  if (!hubs?.length) {
    return null;
  }

  const filteredHubs = filterHubs(hubs, {
    canManageGames: can.manageGames,
    excludeIds: excludeHubIds,
  });
  const sortedHubs = filteredHubs.sort((a, b) => a.title!.localeCompare(b.title!));

  return (
    <div data-testid="hubs-list">
      <h2 className="mb-0 border-0 text-lg font-semibold">{t('Hubs')}</h2>

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
    // Only users who can manage games can see team meta hubs on the list.
    if (hub.title?.includes('Meta|') && !canManageGames) {
      return false;
    }

    // There may be hub IDs marked for specific exclusion (such as those shown in game metadata).
    if (excludeIds?.includes(hub.id)) {
      return false;
    }

    return true;
  });
}
