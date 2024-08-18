import { usePage } from '@inertiajs/react';
import { type FC, useId, useState } from 'react';

import {
  BaseFormControl,
  BaseFormField,
  BaseFormItem,
  BaseFormLabel,
  BaseFormMessage,
} from '@/common/components/+vendor/BaseForm';
import { BaseInput } from '@/common/components/+vendor/BaseInput';

import type { SettingsPageProps } from '../../models';
import { SectionFormCard } from '../SectionFormCard';
import { useChangeEmailAddressForm } from './useChangeEmailAddressForm';

export const ChangeEmailAddressSectionCard: FC = () => {
  const {
    props: { userSettings },
  } = usePage<SettingsPageProps>();

  const [currentEmailAddress, setCurrentEmailAddress] = useState(userSettings.emailAddress ?? '');

  const { form, mutation, onSubmit } = useChangeEmailAddressForm({ setCurrentEmailAddress });

  const visibleEmailFieldId = useId();

  return (
    <SectionFormCard
      headingLabel="Change Email"
      formMethods={form}
      onSubmit={onSubmit}
      isSubmitting={mutation.isPending}
    >
      <div className="@container">
        <div className="flex flex-col gap-5">
          <div className="@xl:flex-row @xl:items-center flex w-full flex-col">
            <label id={visibleEmailFieldId} className="@xl:w-2/5 text-menu-link">
              Current Email Address
            </label>
            <p aria-labelledby={visibleEmailFieldId}>{currentEmailAddress}</p>
          </div>

          <div className="@xl:gap-2 flex flex-col gap-5">
            <BaseFormField
              control={form.control}
              name="newEmail"
              render={({ field }) => (
                <BaseFormItem className="@xl:flex-row @xl:items-center flex w-full flex-col gap-1">
                  <BaseFormLabel className="@xl:w-2/5 text-menu-link">
                    New Email Address
                  </BaseFormLabel>

                  <div className="flex flex-grow flex-col gap-1">
                    <BaseFormControl>
                      <BaseInput
                        type="email"
                        placeholder="enter your new email address here..."
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
              name="confirmEmail"
              render={({ field }) => (
                <BaseFormItem className="@xl:flex-row @xl:items-center flex w-full flex-col gap-1">
                  <BaseFormLabel className="@xl:w-2/5 text-menu-link">
                    Confirm New Email Address
                  </BaseFormLabel>

                  <div className="flex flex-grow flex-col gap-1">
                    <BaseFormControl>
                      <BaseInput
                        type="email"
                        placeholder="confirm your new email address here..."
                        required
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
      </div>
    </SectionFormCard>
  );
};
