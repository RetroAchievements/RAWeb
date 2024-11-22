import { type FC, useId, useState } from 'react';
import { useTranslation } from 'react-i18next';

import {
  BaseFormControl,
  BaseFormField,
  BaseFormItem,
  BaseFormLabel,
  BaseFormMessage,
} from '@/common/components/+vendor/BaseForm';
import { BaseInput } from '@/common/components/+vendor/BaseInput';
import { usePageProps } from '@/common/hooks/usePageProps';

import { SectionFormCard } from '../SectionFormCard';
import { useChangeEmailAddressForm } from './useChangeEmailAddressForm';

export const ChangeEmailAddressSectionCard: FC = () => {
  const { userSettings } = usePageProps<App.Community.Data.UserSettingsPageProps>();

  const { t } = useTranslation();

  const [currentEmailAddress, setCurrentEmailAddress] = useState(userSettings.emailAddress ?? '');

  const { form, mutation, onSubmit } = useChangeEmailAddressForm({ setCurrentEmailAddress });

  const visibleEmailFieldId = useId();

  return (
    <SectionFormCard
      t_headingLabel={t('Change Email')}
      formMethods={form}
      onSubmit={onSubmit}
      isSubmitting={mutation.isPending}
    >
      <div className="@container">
        <div className="flex flex-col gap-5">
          <div className="flex w-full flex-col @xl:flex-row @xl:items-center">
            <label id={visibleEmailFieldId} className="text-menu-link @xl:w-2/5">
              {t('Current Email Address')}
            </label>
            <p aria-labelledby={visibleEmailFieldId}>{currentEmailAddress}</p>
          </div>

          <div className="flex flex-col gap-5 @xl:gap-2">
            <BaseFormField
              control={form.control}
              name="newEmail"
              render={({ field }) => (
                <BaseFormItem className="flex w-full flex-col gap-1 @xl:flex-row @xl:items-center">
                  <BaseFormLabel className="text-menu-link @xl:w-2/5">
                    {t('New Email Address')}
                  </BaseFormLabel>

                  <div className="flex flex-grow flex-col gap-1">
                    <BaseFormControl>
                      <BaseInput
                        type="email"
                        placeholder={t('enter your new email address here...')}
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
                <BaseFormItem className="flex w-full flex-col gap-1 @xl:flex-row @xl:items-center">
                  <BaseFormLabel className="text-menu-link @xl:w-2/5">
                    {t('Confirm New Email Address')}
                  </BaseFormLabel>

                  <div className="flex flex-grow flex-col gap-1">
                    <BaseFormControl>
                      <BaseInput
                        type="email"
                        placeholder={t('confirm your new email address here...')}
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
