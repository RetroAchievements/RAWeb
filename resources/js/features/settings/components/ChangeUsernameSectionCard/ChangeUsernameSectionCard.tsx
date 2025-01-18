import { useAtom } from 'jotai';
import { type FC, useId } from 'react';
import { useTranslation } from 'react-i18next';
import { LuAlertCircle } from 'react-icons/lu';

import {
  BaseAlert,
  BaseAlertDescription,
  BaseAlertTitle,
} from '@/common/components/+vendor/BaseAlert';
import {
  BaseFormControl,
  BaseFormField,
  BaseFormItem,
  BaseFormLabel,
  BaseFormMessage,
} from '@/common/components/+vendor/BaseForm';
import { BaseInput } from '@/common/components/+vendor/BaseInput';
import { usePageProps } from '@/common/hooks/usePageProps';

import { requestedUsernameAtom } from '../../state/settings.atoms';
import { SectionFormCard } from '../SectionFormCard';
import { useChangeUsernameForm } from './useChangeUsernameForm';

export const ChangeUsernameSectionCard: FC = () => {
  const { auth, can } = usePageProps<App.Community.Data.UserSettingsPageProps>();

  const { t } = useTranslation();

  const { form, mutation, onSubmit } = useChangeUsernameForm();

  const [requestedUsername] = useAtom(requestedUsernameAtom);

  const visibleDisplayNameFieldId = useId();
  const requestedDisplayNameFieldId = useId();

  const canShowForm = !requestedUsername && can.createUsernameChangeRequest;

  return (
    <SectionFormCard
      t_headingLabel={t('Change Username')}
      formMethods={form}
      onSubmit={onSubmit}
      isSubmitting={mutation.isPending}
      shouldShowFooter={canShowForm}
    >
      <div className="@container">
        <div className="flex flex-col gap-5">
          {requestedUsername ? <PendingRequestAlert /> : null}
          {!requestedUsername && !can.createUsernameChangeRequest ? <WaitAlert /> : null}

          <div className="flex w-full flex-col @xl:flex-row @xl:items-center">
            <label id={visibleDisplayNameFieldId} className="text-menu-link @xl:w-2/5">
              {t('Current Username')}
            </label>
            <p aria-labelledby={visibleDisplayNameFieldId}>{auth!.user.displayName}</p>
          </div>

          {requestedUsername ? (
            <div className="flex w-full flex-col @xl:flex-row @xl:items-center">
              <label id={requestedDisplayNameFieldId} className="text-menu-link @xl:w-2/5">
                {t('Requested Username')}
              </label>
              <p aria-labelledby={requestedDisplayNameFieldId}>{requestedUsername}</p>
            </div>
          ) : null}

          {canShowForm ? (
            <div className="flex flex-col gap-5 @xl:gap-2">
              <BaseFormField
                control={form.control}
                name="newUsername"
                render={({ field }) => (
                  <BaseFormItem className="flex w-full flex-col gap-1 @xl:flex-row @xl:items-center">
                    <BaseFormLabel className="text-menu-link @xl:w-2/5">
                      {t('New Username')}
                    </BaseFormLabel>

                    <div className="flex flex-grow flex-col gap-1">
                      <BaseFormControl>
                        <BaseInput
                          placeholder={t('enter your new username here...')}
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
                name="confirmUsername"
                render={({ field }) => (
                  <BaseFormItem className="flex w-full flex-col gap-1 @xl:flex-row @xl:items-center">
                    <BaseFormLabel className="text-menu-link @xl:w-2/5">
                      {t('Confirm New Username')}
                    </BaseFormLabel>

                    <div className="flex flex-grow flex-col gap-1">
                      <BaseFormControl>
                        <BaseInput
                          placeholder={t('confirm your new username here...')}
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
          ) : null}
        </div>
      </div>
    </SectionFormCard>
  );
};

const PendingRequestAlert: FC = () => {
  const { t } = useTranslation();

  return (
    <BaseAlert>
      <LuAlertCircle className="size-5" />
      <BaseAlertTitle>{t('You have an active username change request.')}</BaseAlertTitle>
      <BaseAlertDescription>
        {t(
          'Your request will either be approved or it will automatically expire 30 days from when you requested it.',
        )}
      </BaseAlertDescription>
    </BaseAlert>
  );
};

const WaitAlert: FC = () => {
  const { t } = useTranslation();

  return (
    <BaseAlert>
      <LuAlertCircle className="size-5" />
      <BaseAlertTitle>{t('You must wait to change your username.')}</BaseAlertTitle>
      <BaseAlertDescription>
        {t('Each account is limited to one username change every 30 days.')}
      </BaseAlertDescription>
    </BaseAlert>
  );
};
