import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import {
  BaseFormControl,
  BaseFormDescription,
  BaseFormField,
  BaseFormItem,
  BaseFormLabel,
  BaseFormMessage,
} from '@/common/components/+vendor/BaseForm';
import { BaseInput } from '@/common/components/+vendor/BaseInput';
import { usePageProps } from '@/common/hooks/usePageProps';
import type { AuthenticatedUser } from '@/common/models';

import { SectionFormCard } from '../SectionFormCard';
import { useChangePasswordForm } from './useChangePasswordForm';

export const ChangePasswordSectionCard: FC = () => {
  const { form, mutation, onSubmit } = useChangePasswordForm();

  const { auth } = usePageProps<App.Community.Data.UserSettingsPageProps>();

  const { t } = useTranslation();

  const { user } = auth as { user: AuthenticatedUser };

  return (
    <SectionFormCard
      t_headingLabel={t('Change Password')}
      formMethods={form}
      onSubmit={onSubmit}
      isSubmitting={mutation.isPending}
    >
      <div className="@container">
        <div className="flex flex-col gap-5">
          {/* 
            Included for a11y. This helps some password managers suggest new passwords.
            @see https://www.chromium.org/developers/design-documents/create-amazing-password-forms/#use-hidden-fields-for-implicit-information
          */}
          <BaseInput
            autoComplete="username"
            value={user.username ?? ''}
            className="sr-only"
            readOnly
          />

          <BaseFormField
            control={form.control}
            name="currentPassword"
            render={({ field }) => (
              <BaseFormItem className="flex w-full flex-col gap-1 @xl:flex-row @xl:items-center">
                <BaseFormLabel className="text-menu-link @xl:w-2/5">
                  {t('Current Password')}
                </BaseFormLabel>

                <div className="flex flex-grow flex-col gap-1">
                  <BaseFormControl>
                    <BaseInput
                      type="password"
                      placeholder={t('enter your current password here...')}
                      autoComplete="current-password"
                      required
                      {...field}
                    />
                  </BaseFormControl>

                  <BaseFormMessage />
                </div>
              </BaseFormItem>
            )}
          />

          <BaseFormField
            control={form.control}
            name="newPassword"
            render={({ field }) => (
              <BaseFormItem className="flex w-full flex-col gap-1 @xl:flex-row @xl:items-center">
                <BaseFormLabel className="text-menu-link @xl:w-2/5">
                  {t('New Password')}
                </BaseFormLabel>

                <div className="flex flex-grow flex-col gap-1">
                  <BaseFormControl>
                    <BaseInput
                      type="password"
                      placeholder={t('enter a new password here...')}
                      autoComplete="new-password"
                      required
                      minLength={8}
                      {...field}
                    />
                  </BaseFormControl>

                  <BaseFormDescription>{t('Must be at least 8 characters.')}</BaseFormDescription>

                  <BaseFormMessage />
                </div>
              </BaseFormItem>
            )}
          />

          <BaseFormField
            control={form.control}
            name="confirmPassword"
            render={({ field }) => (
              <BaseFormItem className="flex w-full flex-col gap-1 @xl:flex-row @xl:items-center">
                <BaseFormLabel className="text-menu-link @xl:w-2/5">
                  {t('Confirm Password')}
                </BaseFormLabel>

                <div className="flex flex-grow flex-col gap-1">
                  <BaseFormControl>
                    <BaseInput
                      type="password"
                      placeholder={t('confirm your new password here...')}
                      autoComplete="new-password"
                      required
                      minLength={8}
                      {...field}
                    />
                  </BaseFormControl>

                  <BaseFormMessage />
                </div>
              </BaseFormItem>
            )}
          />
        </div>
      </div>
    </SectionFormCard>
  );
};
