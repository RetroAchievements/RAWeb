import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { route } from 'ziggy-js';

import { GameAvatar } from '@/common/components/GameAvatar';
import { cleanHubTitle } from '@/common/utils/cleanHubTitle';

interface PlayableHubsListProps {
  hubs: App.Platform.Data.GameSet[];
}

export const PlayableHubsList: FC<PlayableHubsListProps> = ({ hubs }) => {
  const { t } = useTranslation();

  if (!hubs?.length) {
    return null;
  }

  const sortedHubs = hubs.sort((a, b) => a.title!.localeCompare(b.title!));

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
