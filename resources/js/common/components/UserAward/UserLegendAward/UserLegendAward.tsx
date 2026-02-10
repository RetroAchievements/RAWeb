import type { FC } from 'react';

import { asset } from '@/tall-stack/utils';

import type { AwardProps } from '../UserAward';
import { UserAward } from '../UserAward';

export const UserLegendAward: FC<{ dateAwarded: string }> = ({ dateAwarded }) => {
  /*
  $tooltip = 'Specially Awarded to a Certified RetroAchievements Legend';
        $imagepath = asset('/assets/images/badge/legend.png');
        $imgclass = 'goldimage';
        $linkdest = '';
   */

  const award: AwardProps = {
    dateAwarded,
    tooltip: 'Specially Awarded to a Certified RetroAchievements Legend!',
    imageUrl: asset('/assets/images/badge/legend.png'),
    isGold: true,
    link: '',
  };

  return <UserAward award={award}></UserAward>;
};
