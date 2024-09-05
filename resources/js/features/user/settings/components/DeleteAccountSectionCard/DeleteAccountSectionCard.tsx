import dayjs from 'dayjs';
import { type FC, useState } from 'react';
import { LuAlertCircle } from 'react-icons/lu';

import {
  BaseAlert,
  BaseAlertDescription,
  BaseAlertTitle,
} from '@/common/components/+vendor/BaseAlert';
import { BaseButton } from '@/common/components/+vendor/BaseButton';
import { toastMessage } from '@/common/components/+vendor/BaseToaster';

import { usePageProps } from '../../hooks/usePageProps';
import { SectionStandardCard } from '../SectionStandardCard';
import { useManageAccountDeletion } from './useManageAccountDeletion';

export const DeleteAccountSectionCard: FC = () => {
  const { userSettings } = usePageProps<App.Community.Data.UserSettingsPageProps>();

  const [isDeleteAlreadyRequested, setIsDeleteAlreadyRequested] = useState(
    !!userSettings.deleteRequested,
  );

  const { cancelDeleteMutation, requestDeleteMutation } = useManageAccountDeletion();

  const handleClick = () => {
    const toggleMessage = isDeleteAlreadyRequested
      ? 'Are you sure you want to cancel your request for account deletion?'
      : 'Are you sure you want to request account deletion?';

    if (!confirm(toggleMessage)) {
      return;
    }

    if (isDeleteAlreadyRequested) {
      toastMessage.promise(cancelDeleteMutation.mutateAsync(), {
        loading: 'Loading...',
        success: () => {
          setIsDeleteAlreadyRequested((prev) => !prev);

          return 'Cancelled account deletion.';
        },
        error: 'Something went wrong.',
      });
    } else {
      toastMessage.promise(requestDeleteMutation.mutateAsync(), {
        loading: 'Loading...',
        success: () => {
          setIsDeleteAlreadyRequested((prev) => !prev);

          return 'Requested account deletion.';
        },
        error: 'Something went wrong.',
      });
    }
  };

  const deletionDate = userSettings.deleteRequested
    ? dayjs(userSettings.deleteRequested).add(2, 'weeks')
    : dayjs().add(2, 'weeks');

  return (
    <SectionStandardCard headingLabel="Delete Account">
      <div className="flex flex-col gap-4">
        {isDeleteAlreadyRequested ? (
          <BaseAlert variant="destructive">
            <LuAlertCircle className="h-5 w-5" />
            <BaseAlertTitle>You've requested account deletion.</BaseAlertTitle>
            <BaseAlertDescription>
              Your account will be permanently deleted on {deletionDate.format('MMMM D')}.
            </BaseAlertDescription>
          </BaseAlert>
        ) : null}

        <div>
          <p>After requesting account deletion you may cancel your request within 14 days.</p>
          <p>Your account's username will NOT be available after the deletion.</p>
          <p>Your account's personal data will be cleared from the database permanently.</p>
          <p>Content you wrote in forums, comments, etc. will NOT be removed.</p>
        </div>

        <div className="flex justify-end">
          <BaseButton variant="destructive" onClick={handleClick}>
            {isDeleteAlreadyRequested
              ? 'Cancel Account Deletion Request'
              : 'Request Account Deletion'}
          </BaseButton>
        </div>
      </div>
    </SectionStandardCard>
  );
};
