import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import { BaseDialogFooter } from '@/common/components/+vendor/BaseDialog';
import {
  BaseForm,
  BaseFormControl,
  BaseFormField,
  BaseFormItem,
  BaseFormLabel,
  BaseFormMessage,
} from '@/common/components/+vendor/BaseForm';
import { BaseInput } from '@/common/components/+vendor/BaseInput';

import { useOAuthApplicationForm } from './useOAuthApplicationForm';

interface OAuthApplicationFormProps {
  application: App.Data.OAuthClient;
  onUpdated: () => void;
}

export const OAuthApplicationForm: FC<OAuthApplicationFormProps> = ({ application, onUpdated }) => {
  const { t } = useTranslation();

  const { form, mutation, onSubmit } = useOAuthApplicationForm({
    application,
    onUpdated,
  });

  return (
    <BaseForm {...form}>
      <form className="flex flex-col gap-4" onSubmit={form.handleSubmit(onSubmit)}>
        <BaseFormField
          control={form.control}
          name="name"
          render={({ field }) => (
            <BaseFormItem className="flex flex-col gap-1">
              <BaseFormLabel>{t('Application name')}</BaseFormLabel>
              <BaseFormControl>
                <BaseInput placeholder={t('My RetroAchievements App')} required {...field} />
              </BaseFormControl>
              <BaseFormMessage />
            </BaseFormItem>
          )}
        />

        <BaseFormField
          control={form.control}
          name="redirectUri"
          render={({ field }) => (
            <BaseFormItem className="flex flex-col gap-1">
              <BaseFormLabel>{t('Redirect URI')}</BaseFormLabel>
              <BaseFormControl>
                <BaseInput
                  placeholder={t('https://example.com/oauth/callback', {
                    nsSeparator: null,
                  })}
                  required
                  {...field}
                />
              </BaseFormControl>
              <BaseFormMessage />
            </BaseFormItem>
          )}
        />

        <BaseDialogFooter>
          <BaseButton disabled={mutation.isPending || !form.formState.isDirty} type="submit">
            {t('Save changes')}
          </BaseButton>
        </BaseDialogFooter>
      </form>
    </BaseForm>
  );
};
