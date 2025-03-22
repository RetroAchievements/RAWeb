import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { GameAvatar } from '@/common/components/GameAvatar';
import { cleanHubTitle } from '@/common/utils/cleanHubTitle';

interface HubsListProps {
  hubs: App.Platform.Data.GameSet[];
}

export const HubsList: FC<HubsListProps> = ({ hubs }) => {
  const { t } = useTranslation();

  if (!hubs?.length) {
    return null;
  }

  return (
    <div data-testid="hubs-list">
      <h2 className="mb-0 border-0 text-lg font-semibold">{t('Hubs')}</h2>

      <div className="rounded-lg bg-embed p-1">
        <ul className="zebra-list overflow-hidden rounded-lg">
          {hubs.map((hub) => (
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
