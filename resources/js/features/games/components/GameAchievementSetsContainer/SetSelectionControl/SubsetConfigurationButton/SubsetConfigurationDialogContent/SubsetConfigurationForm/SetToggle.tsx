import type { FC } from 'react';
import type { Control } from 'react-hook-form';
import { useTranslation } from 'react-i18next';

import {
  BaseFormControl,
  BaseFormField,
  BaseFormItem,
  BaseFormLabel,
} from '@/common/components/+vendor/BaseForm';
import { BaseSwitch } from '@/common/components/+vendor/BaseSwitch';
import { cn } from '@/common/utils/cn';
import { BASE_SET_LABEL } from '@/features/games/utils/baseSetLabel';

interface SetToggleProps {
  configurableSet: App.Platform.Data.GameAchievementSet;
  control: Control<{ preferences: Record<string, boolean> }>;
}

export const SetToggle: FC<SetToggleProps> = ({ configurableSet, control }) => {
  const { t } = useTranslation();

  return (
    <BaseFormField
      control={control}
      name={`preferences.${configurableSet.id}`}
      render={({ field }) => (
        <BaseFormItem className="flex w-full items-center justify-between">
          <div className="flex items-center gap-2">
            <img
              src={configurableSet.achievementSet.imageAssetPathUrl}
              alt={configurableSet.title ?? BASE_SET_LABEL}
              className="size-8 rounded-sm"
            />

            <BaseFormLabel>{configurableSet.title ?? BASE_SET_LABEL}</BaseFormLabel>
          </div>

          <div className="flex items-center gap-2">
            <span
              className={cn(
                'hidden text-xs sm:block',
                field.value ? 'text-text' : 'text-neutral-700',
              )}
            >
              {field.value ? t('Opted In') : t('Opted Out')}
            </span>

            <BaseFormControl>
              <BaseSwitch checked={field.value} onCheckedChange={field.onChange} />
            </BaseFormControl>
          </div>
        </BaseFormItem>
      )}
    />
  );
};
