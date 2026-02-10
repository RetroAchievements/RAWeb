import type { FC } from 'react';
import { route } from 'ziggy-js';

import { asset } from '@/tall-stack/utils';

import type { AwardProps } from '../UserAward';
import { UserAward } from '../UserAward';

export const UserPatreonAward: FC<{ dateAwarded: string; size?: number }> = ({
  dateAwarded,
  size,
}) => {
  /*
  $tooltip = 'Awarded for being a Patreon supporter! Thank-you so much for your support!';
        $imagepath = asset('/assets/images/badge/patreon.png');
        $imgclass = 'goldimage';
        $linkdest = route('patreon-supporter.index');
   */

  const award: AwardProps = {
    dateAwarded,
    tooltip: 'Awarded for being a Patreon subscriber! Thank you fo much for your support!',
    imageUrl: asset('/assets/images/badge/patreon.png'),
    isGold: true,
    link: route('patreon-supporter.index'),
  };

  return <UserAward award={award} size={size}></UserAward>;
};
