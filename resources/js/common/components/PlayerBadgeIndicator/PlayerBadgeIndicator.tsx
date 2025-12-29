import type { FC } from 'react';

import { AwardIndicator } from '../AwardIndicator';

interface PlayerBadgeIndicatorProps {
  playerBadge: App.Platform.Data.PlayerBadge;

  className?: string;
}

export const PlayerBadgeIndicator: FC<PlayerBadgeIndicatorProps> = ({ playerBadge, className }) => {
  const { awardType, awardTier } = playerBadge;

  let indicator: 'mastery' | 'completion' | 'beaten-hardcore' | 'beaten-softcore' =
    'beaten-softcore';
  if (awardType === 'mastery' && awardTier) {
    indicator = 'mastery';
  } else if (awardType === 'mastery' && !awardTier) {
    indicator = 'completion';
  } else if (awardType === 'game_beaten' && awardTier) {
    indicator = 'beaten-hardcore';
  }

  return <AwardIndicator awardKind={indicator} className={className} />;
};
