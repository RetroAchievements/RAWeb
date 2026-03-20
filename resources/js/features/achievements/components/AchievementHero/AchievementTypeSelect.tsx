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

interface AchievementTypeSelectProps {
  form: UseFormReturn<AchievementQuickEditFormValues>;
  isSubset: boolean;
}

export const AchievementTypeSelect: FC<AchievementTypeSelectProps> = ({ form, isSubset }) => {
  const { t } = useTranslation();

  return (
    <Controller
      control={form.control}
      name="type"
      render={({ field }) => (
        <BaseSelect value={field.value} onValueChange={field.onChange}>
          <BaseSelectTrigger
            aria-label={t('Achievement type')}
            className={cn(
              'h-auto w-auto shrink-0 gap-1 border-none bg-transparent p-0 pl-1 text-xs shadow-none focus:ring-0',
              editableAchievementClassNames.field,
            )}
          >
            <BaseSelectValue placeholder={t('None')} />
          </BaseSelectTrigger>

          <BaseSelectContent>
            <BaseSelectItem value="none">{t('None')}</BaseSelectItem>
            <BaseSelectItem value="missable">{t('Missable')}</BaseSelectItem>
            {!isSubset ? (
              <>
                <BaseSelectItem value="progression">{t('Progression')}</BaseSelectItem>
                <BaseSelectItem value="win_condition">{t('Win Condition')}</BaseSelectItem>
              </>
            ) : null}
          </BaseSelectContent>
        </BaseSelect>
      )}
    />
  );
};
