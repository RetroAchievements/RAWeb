import type { FC } from 'react';

import {
  BaseFormControl,
  BaseFormDescription,
  BaseFormField,
  BaseFormItem,
  BaseFormLabel,
  BaseFormMessage,
} from '@/common/components/+vendor/BaseForm';
import { BaseInput } from '@/common/components/+vendor/BaseInput';

import { usePageProps } from '../../hooks/usePageProps';
import { SectionFormCard } from '../SectionFormCard';
import { useChangePasswordForm } from './useChangePasswordForm';

export const ChangePasswordSectionCard: FC = () => {
  const { form, mutation, onSubmit } = useChangePasswordForm();

  const { auth } = usePageProps<App.Community.Data.UserSettingsPageProps>();

  return (
    <SectionFormCard
      headingLabel="Change Password"
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
            value={auth?.user.username ?? ''}
            className="sr-only"
            readOnly
          />

          <BaseFormField
            control={form.control}
            name="currentPassword"
            render={({ field }) => (
              <BaseFormItem className="flex w-full flex-col gap-1 @xl:flex-row @xl:items-center">
                <BaseFormLabel className="text-menu-link @xl:w-2/5">Current Password</BaseFormLabel>

                <div className="flex flex-grow flex-col gap-1">
                  <BaseFormControl>
                    <BaseInput
                      type="password"
                      placeholder="enter your current password here..."
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
                <BaseFormLabel className="text-menu-link @xl:w-2/5">New Password</BaseFormLabel>

                <div className="flex flex-grow flex-col gap-1">
                  <BaseFormControl>
                    <BaseInput
                      type="password"
                      placeholder="enter a new password here..."
                      autoComplete="new-password"
                      required
                      minLength={8}
                      {...field}
                    />
                  </BaseFormControl>

                  <BaseFormDescription>Must be at least 8 characters.</BaseFormDescription>

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
                <BaseFormLabel className="text-menu-link @xl:w-2/5">Confirm Password</BaseFormLabel>

                <div className="flex flex-grow flex-col gap-1">
                  <BaseFormControl>
                    <BaseInput
                      type="password"
                      placeholder="confirm your new password here..."
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
