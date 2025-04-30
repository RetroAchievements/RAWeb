import type { FC } from 'react';

import { useFormatNumber } from '@/common/hooks/useFormatNumber';

import { WeightedPointsContainer } from '../../WeightedPointsContainer';

interface AchievementPointsProps {
  points: number;
  isEvent: boolean;

  pointsWeighted?: number;
}

export const AchievementPoints: FC<AchievementPointsProps> = ({
  isEvent,
  points,
  pointsWeighted,
}) => {
  const { formatNumber } = useFormatNumber();

  if (!points || (isEvent && points === 1)) {
    return null;
  }

  return (
    <span className="inline-flex gap-1 text-[12px]">
      <span>{`(${points})`}</span>

      {!isEvent && pointsWeighted ? (
        <WeightedPointsContainer>{`(${formatNumber(pointsWeighted)})`}</WeightedPointsContainer>
      ) : null}
    </span>
  );
};
