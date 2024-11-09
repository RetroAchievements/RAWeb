import type { FC } from 'react';
import { useFormContext } from 'react-hook-form';
import { useTranslation } from 'react-i18next';

import {
  BaseFormControl,
  BaseFormField,
  BaseFormItem,
  BaseFormLabel,
} from '@/common/components/+vendor/BaseForm';
import { BaseToggleGroup, BaseToggleGroupItem } from '@/common/components/+vendor/BaseToggleGroup';

import type { CreateAchievementTicketFormValues } from './useCreateAchievementTicketForm';

export const SessionModeToggleGroup: FC = () => {
  const { t } = useTranslation();

  const form = useFormContext<CreateAchievementTicketFormValues>();

  return (
    <BaseFormField
      control={form.control}
      name="mode"
      render={({ field }) => (
        <BaseFormItem className="flex w-full flex-col gap-1 sm:flex-row sm:items-center">
          <BaseFormLabel className="text-menu-link sm:mt-[13px] sm:min-w-36 sm:self-start">
            {t('Mode')}
          </BaseFormLabel>

          <BaseFormControl className="self-end sm:self-auto">
            <BaseToggleGroup
              type="single"
              variant="outline"
              value={field.value}
              onValueChange={field.onChange}
            >
              <BaseToggleGroupItem value="hardcore" aria-label={t('Toggle hardcore')}>
                {t('Hardcore')}
              </BaseToggleGroupItem>

              <BaseToggleGroupItem value="softcore" aria-label={t('Toggle softcore')}>
                {t('Softcore')}
              </BaseToggleGroupItem>
            </BaseToggleGroup>
          </BaseFormControl>
        </BaseFormItem>
      )}
    />
  );
};
