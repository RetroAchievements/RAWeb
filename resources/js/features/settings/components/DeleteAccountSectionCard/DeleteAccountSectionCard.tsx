import dayjs from 'dayjs';
import { type FC, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { LuAlertCircle } from 'react-icons/lu';

import {
  BaseAlert,
  BaseAlertDescription,
  BaseAlertTitle,
} from '@/common/components/+vendor/BaseAlert';
import { BaseButton } from '@/common/components/+vendor/BaseButton';
import { toastMessage } from '@/common/components/+vendor/BaseToaster';
import { usePageProps } from '@/common/hooks/usePageProps';

import { SectionStandardCard } from '../SectionStandardCard';
import { useManageAccountDeletion } from './useManageAccountDeletion';

export const DeleteAccountSectionCard: FC = () => {
  const { userSettings } = usePageProps<App.Community.Data.UserSettingsPageProps>();

  const { t } = useTranslation();

  const [isDeleteAlreadyRequested, setIsDeleteAlreadyRequested] = useState(
    !!userSettings.deleteRequested,
  );

  const { cancelDeleteMutation, requestDeleteMutation } = useManageAccountDeletion();

  const handleClick = () => {
    const toggleMessage = isDeleteAlreadyRequested
      ? t('Are you sure you want to cancel your request for account deletion?')
      : t('Are you sure you want to request account deletion?');

    if (!confirm(toggleMessage)) {
      return;
    }

    if (isDeleteAlreadyRequested) {
      toastMessage.promise(cancelDeleteMutation.mutateAsync(), {
        loading: t('Loading...'),
        success: () => {
          setIsDeleteAlreadyRequested((prev) => !prev);

          return t('Cancelled account deletion.');
        },
        error: t('Something went wrong.'),
      });
    } else {
      toastMessage.promise(requestDeleteMutation.mutateAsync(), {
        loading: t('Loading...'),
        success: () => {
          setIsDeleteAlreadyRequested((prev) => !prev);

          return t('Requested account deletion.');
        },
        error: t('Something went wrong.'),
      });
    }
  };

  const deletionDate = userSettings.deleteRequested
    ? dayjs(userSettings.deleteRequested).add(2, 'weeks')
    : dayjs().add(2, 'weeks');

  return (
    <SectionStandardCard t_headingLabel={t('Delete Account')}>
      <div className="flex flex-col gap-4">
        {isDeleteAlreadyRequested ? (
          <BaseAlert variant="destructive">
            <LuAlertCircle className="size-5" />
            <BaseAlertTitle>{t("You've requested account deletion.")}</BaseAlertTitle>
            <BaseAlertDescription>
              {t('Your account will be permanently deleted on {{date}}', {
                date: deletionDate.format('MMMM D'),
              })}
            </BaseAlertDescription>
          </BaseAlert>
        ) : null}

        <div>
          <p>
            {t('After requesting account deletion you may cancel your request within 14 days.')}
          </p>
          <p>{t("Your account's username will NOT be available after the deletion.")}</p>
          <p>{t("Your account's personal data will be cleared from the database permanently.")}</p>
          <p>{t('Content you wrote in forums, comments, etc. will NOT be removed.')}</p>
        </div>

        <div className="flex justify-end">
          <BaseButton variant="destructive" onClick={handleClick}>
            {isDeleteAlreadyRequested
              ? t('Cancel Account Deletion Request')
              : t('Request Account Deletion')}
          </BaseButton>
        </div>
      </div>
    </SectionStandardCard>
  );
};
