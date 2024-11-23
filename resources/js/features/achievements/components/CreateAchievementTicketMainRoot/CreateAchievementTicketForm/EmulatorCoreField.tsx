import type { FC } from 'react';
import { useFormContext } from 'react-hook-form';
import { useTranslation } from 'react-i18next';

import {
  BaseFormControl,
  BaseFormField,
  BaseFormItem,
  BaseFormLabel,
} from '@/common/components/+vendor/BaseForm';
import { BaseInput } from '@/common/components/+vendor/BaseInput';

import type { CreateAchievementTicketFormValues } from './useCreateAchievementTicketForm';

export const EmulatorCoreField: FC = () => {
  const { t } = useTranslation();

  const form = useFormContext<CreateAchievementTicketFormValues>();

  return (
    <BaseFormField
      control={form.control}
      name="core"
      render={({ field }) => (
        <BaseFormItem className="flex w-full flex-col gap-1 sm:flex-row sm:items-center">
          <BaseFormLabel className="w-36 text-menu-link sm:mt-[13px] sm:min-w-36 sm:self-start">
            {t('Emulator Core')}
          </BaseFormLabel>

          <div className="flex w-full flex-col gap-1">
            <BaseFormControl>
              <BaseInput
                placeholder={t('enter the emulator core...')}
                className="w-full md:w-96"
                {...field}
              />
            </BaseFormControl>
          </div>
        </BaseFormItem>
      )}
    />
  );
};
