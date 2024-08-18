import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { type FC } from 'react';
import { LuAlertCircle } from 'react-icons/lu';
import { route } from 'ziggy-js';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import { toastMessage } from '@/common/components/+vendor/BaseToaster';

export const ManageConnectApiKey: FC = () => {
  const mutation = useMutation({
    mutationFn: () => {
      return axios.delete(route('settings.keys.connect.destroy'));
    },
  });

  const handleResetApiKeyClick = () => {
    if (
      !confirm(
        'Are you sure you want to reset your Connect API key? This will log you out of all emulators.',
      )
    ) {
      return;
    }

    toastMessage.promise(mutation.mutateAsync(), {
      loading: 'Resetting...',
      success: 'Your Connect API key has been reset.',
      error: 'Something went wrong.',
    });
  };

  return (
    <div className="@container">
      <div className="@lg:grid @lg:grid-cols-4 flex flex-col">
        <p className="w-48 text-menu-link">Connect API Key</p>

        <div className="col-span-3 flex flex-col gap-2">
          <p>
            Your Connect API key is used by emulators to keep you logged in. Resetting the key will
            log you out of all emulators.
          </p>

          <BaseButton
            className="@lg:max-w-fit flex w-full gap-2"
            size="sm"
            variant="destructive"
            onClick={handleResetApiKeyClick}
          >
            <LuAlertCircle className="h-4 w-4" />
            Reset Connect API Key
          </BaseButton>
        </div>
      </div>
    </div>
  );
};
