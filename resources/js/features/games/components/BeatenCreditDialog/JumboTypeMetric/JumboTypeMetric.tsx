import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { BaseProgress } from '@/common/components/+vendor/BaseProgress';
import { RaProgression } from '@/common/components/RaProgression';
import { RaWinCondition } from '@/common/components/RaWinCondition';

interface JumboTypeMetricProps {
  current: number;
  total: number;
  type: 'progression' | 'win_condition';
}

export const JumboTypeMetric: FC<JumboTypeMetricProps> = ({ current, total, type }) => {
  const { t } = useTranslation();

  const Icon = type === 'progression' ? RaProgression : RaWinCondition;

  return (
    <div className="rounded-lg border border-neutral-700 bg-neutral-800 p-4">
      <div className="flex flex-col gap-2">
        <p className="flex items-center justify-between text-neutral-400">
          <span>
            {type === 'progression' ? t('Progression') : null}
            {type === 'win_condition' ? t('Win Condition') : null}
          </span>

          <Icon className="size-5" />
        </p>

        <p className="text-2xl font-bold text-neutral-300">
          {current}
          {'/'}
          {total}
        </p>

        <BaseProgress
          className="h-2"
          max={total}
          segments={[
            {
              value: current,
              className: type === 'progression' ? 'bg-green-600' : 'bg-amber-600',
            },
          ]}
        />
      </div>
    </div>
  );
};
