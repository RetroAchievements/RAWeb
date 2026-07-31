import { useAtomValue } from 'jotai';
import { type FC, useId } from 'react';
import { useTranslation } from 'react-i18next';
import { LuCircleAlert } from 'react-icons/lu';

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
import { ChangeUsernameConfirmDialog } from './ChangeUsernameConfirmDialog';
import { useChangeUsernameForm } from './useChangeUsernameForm';

export const ChangeUsernameSectionCard: FC = () => {
  const { auth, can } = usePageProps<App.Community.Data.UserSettingsPageProps>();
  const { t } = useTranslation();

  const {
    checkMutation,
    form,
    isConfirmDialogOpen,
    createMutation,
    onConfirmUsernameChange,
    onSubmit,
    pendingUsername,
    setIsConfirmDialogOpen,
  } = useChangeUsernameForm();

  const requestedUsername = useAtomValue(requestedUsernameAtom);

  const visibleDisplayNameFieldId = useId();
  const requestedDisplayNameFieldId = useId();

  const canShowForm = !requestedUsername && can.createUsernameChangeRequest;

  return (
    <SectionFormCard
      t_headingLabel={t('Change Username')}
      formMethods={form}
      onSubmit={onSubmit}
      isSubmitting={checkMutation.isPending || createMutation.isPending}
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

              <p aria-labelledby={requestedDisplayNameFieldId} data-sentry-mask>
                {requestedUsername}
              </p>
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

                    <div className="flex grow flex-col gap-1">
                      <BaseFormControl>
                        <BaseInput
                          placeholder={t('Enter your new username')}
                          required
                          autoComplete="off"
                          data-1p-ignore
                          data-lpignore="true"
                          data-bwignore
                          data-form-type="other"
                          minLength={4}
                          maxLength={20}
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

                    <div className="flex grow flex-col gap-1">
                      <BaseFormControl>
                        <BaseInput
                          placeholder={t('Confirm your new username')}
                          required
                          autoComplete="off"
                          data-1p-ignore
                          data-lpignore="true"
                          data-bwignore
                          data-form-type="other"
                          minLength={4}
                          maxLength={20}
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

      <ChangeUsernameConfirmDialog
        isOpen={isConfirmDialogOpen}
        isSubmitting={createMutation.isPending}
        requestedUsername={pendingUsername}
        onConfirm={onConfirmUsernameChange}
        onOpenChange={setIsConfirmDialogOpen}
      />
    </SectionFormCard>
  );
};

const PendingRequestAlert: FC = () => {
  const { t } = useTranslation();

  return (
    <BaseAlert>
      <LuCircleAlert className="size-5" />
      <BaseAlertTitle>{t('Your username change request is being reviewed.')}</BaseAlertTitle>
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
      <LuCircleAlert className="size-5" />
      <BaseAlertTitle>{t('Your username cannot be changed right now.')}</BaseAlertTitle>
      <BaseAlertDescription>
        {t(
          "You can request another change after your previous request's 30-day cooldown period has ended.",
        )}
      </BaseAlertDescription>
    </BaseAlert>
  );
};
