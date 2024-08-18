import { usePage } from '@inertiajs/react';
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

import type { SettingsPageProps } from '../../models';
import { SectionFormCard } from '../SectionFormCard';
import { useChangePasswordForm } from './useChangePasswordForm';

export const ChangePasswordSectionCard: FC = () => {
  const { form, mutation, onSubmit } = useChangePasswordForm();

  const {
    props: { auth },
  } = usePage<SettingsPageProps>();

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
              <BaseFormItem className="@xl:flex-row @xl:items-center flex w-full flex-col gap-1">
                <BaseFormLabel className="@xl:w-2/5 text-menu-link">Current Password</BaseFormLabel>

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
              <BaseFormItem className="@xl:flex-row @xl:items-center flex w-full flex-col gap-1">
                <BaseFormLabel className="@xl:w-2/5 text-menu-link">New Password</BaseFormLabel>

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
              <BaseFormItem className="@xl:flex-row @xl:items-center flex w-full flex-col gap-1">
                <BaseFormLabel className="@xl:w-2/5 text-menu-link">Confirm Password</BaseFormLabel>

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
