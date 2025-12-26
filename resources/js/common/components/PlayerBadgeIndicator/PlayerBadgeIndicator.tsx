import type { FC } from 'react';

import { AwardIndicator } from '../AwardIndicator';

interface PlayerBadgeIndicatorProps {
  playerBadge: App.Platform.Data.PlayerBadge;

  className?: string;
}

export const PlayerBadgeIndicator: FC<PlayerBadgeIndicatorProps> = ({ playerBadge, className }) => {
  const { awardType, awardDataExtra } = playerBadge;

  let indicator: 'mastery' | 'completion' | 'beaten-hardcore' | 'beaten-softcore' =
    'beaten-softcore';
  if (awardType === 'mastery' && awardDataExtra) {
    indicator = 'mastery';
  } else if (awardType === 'mastery' && !awardDataExtra) {
    indicator = 'completion';
  } else if (awardType === 'game_beaten' && awardDataExtra) {
    indicator = 'beaten-hardcore';
  }

  return <AwardIndicator awardKind={indicator} className={className} />;
};
