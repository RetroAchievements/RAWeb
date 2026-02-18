import type { FC } from 'react';
import { Trans } from 'react-i18next';

import { WeightedPointsContainer } from '@/common/components/WeightedPointsContainer';

interface PointsLabelsProps {
  points?: number;
  pointsWeighted?: number;
}

export const PointsLabels: FC<PointsLabelsProps> = ({ points, pointsWeighted }) => {
  return (
    <div className="flex gap-3 text-xs">
      <p className="light:text-neutral-900">
        <Trans
          i18nKey="<1>{{val, number}}</1> points"
          count={points}
          values={{ val: points }}
          components={{ 1: <span className="font-semibold" /> }}
        />
      </p>

      <WeightedPointsContainer>
        <p className="text-neutral-400">
          <Trans
            i18nKey="<1>{{val, number}}</1> RetroPoints"
            count={pointsWeighted}
            values={{ val: pointsWeighted }}
            components={{ 1: <span className="font-semibold" /> }}
          />
        </p>
      </WeightedPointsContainer>
    </div>
  );
};
