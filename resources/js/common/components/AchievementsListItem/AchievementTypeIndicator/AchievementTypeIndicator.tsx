import type { FC, ReactNode } from 'react';
import { useTranslation } from 'react-i18next';

import { cn } from '@/common/utils/cn';
import type { TranslatedString } from '@/types/i18next';

import { BaseTooltip, BaseTooltipContent, BaseTooltipTrigger } from '../../+vendor/BaseTooltip';
import { RaMissable } from '../../RaMissable';
import { RaProgression } from '../../RaProgression';
import { RaWinCondition } from '../../RaWinCondition';

interface AchievementTypeIndicatorProps {
  type: NonNullable<App.Platform.Data.Achievement['type']>;
}

export const AchievementTypeIndicator: FC<AchievementTypeIndicatorProps> = ({ type }) => {
  const { t } = useTranslation();

  const typeMetaMap: Record<
    AchievementTypeIndicatorProps['type'],
    { label: TranslatedString; icon: ReactNode }
  > = {
    missable: { icon: <RaMissable className="size-[18px]" />, label: t('Missable') },
    progression: { icon: <RaProgression className="size-[18px]" />, label: t('Progression') },
    win_condition: { icon: <RaWinCondition className="size-[18px]" />, label: t('Win Condition') },
  };

  const { icon, label } = typeMetaMap[type];

  return (
    <BaseTooltip>
      <BaseTooltipTrigger asChild>
        <div
          className={cn(
            'group flex items-center rounded-full border bg-embed p-1',
            'text-neutral-200 light:border-neutral-300 light:bg-neutral-50 light:text-neutral-500',

            type === 'missable' ? 'border-dashed border-stone-500' : 'border-transparent',
          )}
        >
          <div aria-label={label}>{icon}</div>
        </div>
      </BaseTooltipTrigger>

      <BaseTooltipContent>{label}</BaseTooltipContent>
    </BaseTooltip>
  );
};
