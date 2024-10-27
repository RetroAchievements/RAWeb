import { useLaravelReactI18n } from 'laravel-react-i18n';
import type { FC } from 'react';
import { useFormContext } from 'react-hook-form';

import {
  BaseFormControl,
  BaseFormDescription,
  BaseFormField,
  BaseFormItem,
  BaseFormLabel,
} from '@/common/components/+vendor/BaseForm';
import { BaseInput } from '@/common/components/+vendor/BaseInput';

import type { CreateAchievementTicketFormValues } from './useCreateAchievementTicketForm';

export const EmulatorVersionField: FC = () => {
  const { t } = useLaravelReactI18n();

  const form = useFormContext<CreateAchievementTicketFormValues>();

  return (
    <BaseFormField
      control={form.control}
      name="emulatorVersion"
      render={({ field }) => (
        <BaseFormItem className="flex w-full flex-col gap-1 sm:flex-row sm:items-center">
          <BaseFormLabel className="text-menu-link sm:mt-[13px] sm:min-w-36 sm:self-start">
            {t('Emulator Version')}
          </BaseFormLabel>

          <div className="flex flex-col gap-1">
            <BaseFormControl>
              <BaseInput
                maxLength={10}
                placeholder={t('enter the emulator version...')}
                className="w-full md:w-96"
                {...field}
              />
            </BaseFormControl>

            <BaseFormDescription className="light:text-neutral-800">
              {t(
                'Including your correct emulator version helps developers more quickly identify and resolve issues.',
              )}
            </BaseFormDescription>
          </div>
        </BaseFormItem>
      )}
    />
  );
};
