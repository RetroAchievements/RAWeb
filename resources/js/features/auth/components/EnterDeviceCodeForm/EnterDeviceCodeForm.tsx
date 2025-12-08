import { type FC, useEffect } from 'react';
import { useTranslation } from 'react-i18next';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import { BaseCardContent, BaseCardFooter } from '@/common/components/+vendor/BaseCard';
import {
  BaseForm,
  BaseFormControl,
  BaseFormField,
  BaseFormItem,
  BaseFormLabel,
  BaseFormMessage,
} from '@/common/components/+vendor/BaseForm';
import { BaseInput } from '@/common/components/+vendor/BaseInput';
import type { TranslatedString } from '@/types/i18next';

import { useEnterDeviceCodeForm } from './useEnterDeviceCodeForm';

interface EnterDeviceCodeFormProps {
  serverError?: string;
}

export const EnterDeviceCodeForm: FC<EnterDeviceCodeFormProps> = ({ serverError }) => {
  const { t } = useTranslation();

  const { form, isNavigating, onSubmit } = useEnterDeviceCodeForm();

  // Sync server-side errors to the form.
  useEffect(() => {
    if (serverError) {
      form.setError('userCode', { message: t('Incorrect code.') });
    }
  }, [serverError, form, t]);

  return (
    <BaseForm {...form}>
      <form onSubmit={form.handleSubmit(onSubmit)} name="enter-device-code">
        <BaseCardContent className="px-0 pb-6 pt-0">
          <BaseFormField
            control={form.control}
            name="userCode"
            render={({ field }) => (
              <BaseFormItem className="flex flex-col gap-2">
                <BaseFormLabel>{t('One-time code')}</BaseFormLabel>
                <BaseFormControl>
                  <BaseInput
                    {...field}
                    onChange={(e) => {
                      // Strip non-alphanumeric, uppercase, limit to 8 chars.
                      const raw = e.target.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
                      const limited = raw.slice(0, 8);

                      // Insert a dash after the first 4 characters.
                      const formatted =
                        limited.length > 4 ? `${limited.slice(0, 4)}-${limited.slice(4)}` : limited;

                      field.onChange(formatted);
                    }}
                    placeholder={'XXXX-XXXX' as TranslatedString}
                    maxLength={9}
                    className="h-12 px-4 text-center font-mono text-lg uppercase tracking-widest placeholder:text-neutral-600"
                  />
                </BaseFormControl>

                <BaseFormMessage className="text-xs" />
              </BaseFormItem>
            )}
          />
        </BaseCardContent>

        <BaseCardFooter className="flex flex-col gap-4 p-0">
          <BaseButton size="lg" className="w-full" disabled={isNavigating}>
            {t('Connect')}
          </BaseButton>

          <p className="text-2xs text-neutral-500">
            {t("Make sure you're authorizing an app you trust.")}
          </p>
        </BaseCardFooter>
      </form>
    </BaseForm>
  );
};
