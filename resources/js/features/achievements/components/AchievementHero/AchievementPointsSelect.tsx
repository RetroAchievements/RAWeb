import type { FC } from 'react';
import type { UseFormReturn } from 'react-hook-form';
import { Controller } from 'react-hook-form';
import { useTranslation } from 'react-i18next';

import {
  BaseSelect,
  BaseSelectContent,
  BaseSelectItem,
  BaseSelectTrigger,
  BaseSelectValue,
} from '@/common/components/+vendor/BaseSelect';
import { cn } from '@/common/utils/cn';

import type { AchievementQuickEditFormValues } from '../../hooks/useAchievementQuickEditForm';
import { editableAchievementClassNames } from './editableAchievementClassNames';

const VALID_POINTS = [0, 1, 2, 3, 4, 5, 10, 25, 50, 100] as const;

interface AchievementPointsSelectProps {
  form: UseFormReturn<AchievementQuickEditFormValues>;
}

export const AchievementPointsSelect: FC<AchievementPointsSelectProps> = ({ form }) => {
  const { t } = useTranslation();

  return (
    <div className="flex items-center gap-1.5 text-xs">
      <Controller
        control={form.control}
        name="points"
        render={({ field }) => (
          <BaseSelect value={String(field.value)} onValueChange={(v) => field.onChange(Number(v))}>
            <BaseSelectTrigger
              aria-label={t('Achievement points')}
              className={cn(
                'h-auto w-auto gap-1 border-none bg-transparent p-0 pr-1 text-xs font-semibold shadow-none focus:ring-0',
                editableAchievementClassNames.field,
              )}
            >
              <BaseSelectValue />
            </BaseSelectTrigger>

            <BaseSelectContent>
              {VALID_POINTS.map((pts) => (
                <BaseSelectItem key={pts} value={String(pts)}>
                  {pts}
                </BaseSelectItem>
              ))}
            </BaseSelectContent>
          </BaseSelect>
        )}
      />

      <span>{t('Points')}</span>
    </div>
  );
};
