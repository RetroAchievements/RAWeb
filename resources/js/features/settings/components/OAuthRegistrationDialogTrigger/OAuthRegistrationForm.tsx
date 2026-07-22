import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import { BaseCheckbox } from '@/common/components/+vendor/BaseCheckbox';
import { BaseDialogFooter } from '@/common/components/+vendor/BaseDialog';
import {
  BaseForm,
  BaseFormControl,
  BaseFormDescription,
  BaseFormField,
  BaseFormItem,
  BaseFormLabel,
  BaseFormMessage,
} from '@/common/components/+vendor/BaseForm';
import { BaseInput } from '@/common/components/+vendor/BaseInput';

import { useOAuthRegistrationForm } from './useOAuthRegistrationForm';

interface OAuthRegistrationFormProps {
  onSuccess: (credentials: App.Data.OAuthClientCredentials) => void;
}

export const OAuthRegistrationForm: FC<OAuthRegistrationFormProps> = ({ onSuccess }) => {
  const { t } = useTranslation();
  const { form, mutation, onSubmit } = useOAuthRegistrationForm({ onSuccess });

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
                <BaseInput
                  autoComplete="organization"
                  placeholder={t('My RetroAchievements App')}
                  required
                  {...field}
                />
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
                  autoComplete="url"
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

        <BaseFormField
          control={form.control}
          name="isPublic"
          render={({ field }) => (
            <BaseFormItem className="flex flex-col gap-1">
              <div className="flex items-center gap-2">
                <BaseFormControl>
                  <BaseCheckbox
                    checked={field.value}
                    onCheckedChange={(checked) => field.onChange(checked === true)}
                  />
                </BaseFormControl>
                <BaseFormLabel>{t('Public client (PKCE)')}</BaseFormLabel>
              </div>
              <BaseFormDescription className="text-sm">
                {t(
                  'For browser, mobile, CLI, and other applications that cannot securely store a client secret.',
                )}
              </BaseFormDescription>
            </BaseFormItem>
          )}
        />

        <BaseDialogFooter>
          <BaseButton disabled={mutation.isPending} type="submit">
            {t('Register application')}
          </BaseButton>
        </BaseDialogFooter>
      </form>
    </BaseForm>
  );
};
