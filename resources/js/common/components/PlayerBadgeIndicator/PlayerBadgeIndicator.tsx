import type { FC } from 'react';

import { AwardType } from '@/common/utils/generatedAppConstants';

import { AwardIndicator } from '../AwardIndicator';

interface PlayerBadgeIndicatorProps {
  playerBadge: App.Platform.Data.PlayerBadge;

  className?: string;
}

export const PlayerBadgeIndicator: FC<PlayerBadgeIndicatorProps> = ({ playerBadge, className }) => {
  const { awardType, awardDataExtra } = playerBadge;

  let indicator: 'mastery' | 'completion' | 'beaten-hardcore' | 'beaten-softcore' =
    'beaten-softcore';
  if (awardType === AwardType.Mastery && awardDataExtra) {
    indicator = 'mastery';
  } else if (awardType === AwardType.Mastery && !awardDataExtra) {
    indicator = 'completion';
  } else if (awardType === AwardType.GameBeaten && awardDataExtra) {
    indicator = 'beaten-hardcore';
  }

  return <AwardIndicator awardKind={indicator} className={className} />;
};
