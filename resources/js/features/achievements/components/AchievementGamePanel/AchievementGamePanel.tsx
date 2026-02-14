import type { FC } from 'react';

import { GameAvatar } from '@/common/components/GameAvatar';
import { usePageProps } from '@/common/hooks/usePageProps';

export const AchievementGamePanel: FC = () => {
  const { achievement } = usePageProps<App.Platform.Data.AchievementShowPageProps>();

  const game = achievement.game as App.Platform.Data.Game;

  return (
    <div className="rounded bg-embed p-2 light:border light:border-neutral-200 light:bg-neutral-50">
      <GameAvatar {...game} size={40} showSystemChip={true} gameTitleClassName="line-clamp-1" />
    </div>
  );
};
