import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { cleanHubTitle } from '@/common/utils/cleanHubTitle';

interface HubResultDisplayProps {
  hub: App.Platform.Data.GameSet;
}

export const HubResultDisplay: FC<HubResultDisplayProps> = ({ hub }) => {
  const { t } = useTranslation();

  return (
    <div className="flex items-center gap-3">
      <img src={hub.badgeUrl!} alt={hub.title!} className="size-10 rounded" />

      <div className="flex flex-col gap-0.5">
        <div className="line-clamp-1 font-medium text-link">{cleanHubTitle(hub.title!)}</div>

        <div className="text-xs text-neutral-400 light:text-neutral-600">
          {t('{{val, number}} games', {
            count: hub.gameCount,
            val: hub.gameCount,
          })}
        </div>
      </div>
    </div>
  );
};
