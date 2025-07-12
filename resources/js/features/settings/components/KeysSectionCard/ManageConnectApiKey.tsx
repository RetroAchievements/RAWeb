import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { type FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuCircleAlert } from 'react-icons/lu';
import { route } from 'ziggy-js';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import { toastMessage } from '@/common/components/+vendor/BaseToaster';

export const ManageConnectApiKey: FC = () => {
  const { t } = useTranslation();

  const mutation = useMutation({
    mutationFn: () => {
      return axios.delete(route('api.settings.keys.connect.destroy'));
    },
  });

  const handleResetApiKeyClick = () => {
    if (!confirm(t('Are you sure you want to sign out of all emulators?'))) {
      return;
    }

    toastMessage.promise(mutation.mutateAsync(), {
      loading: t('Signing out...'),
      success: t('You have been signed out of all emulators.'),
      error: t('Something went wrong.'),
    });
  };

  return (
    <div className="@container">
      <div className="flex flex-col @lg:grid @lg:grid-cols-4 @lg:items-center">
        <p className="w-48 text-menu-link">{t('Emulator Sessions')}</p>

        <div className="col-span-3 flex flex-col gap-2">
          <BaseButton
            className="flex w-full gap-2 @lg:max-w-fit"
            size="sm"
            variant="destructive"
            onClick={handleResetApiKeyClick}
          >
            <LuCircleAlert className="h-4 w-4" />
            {t('Sign Out of All Emulators')}
          </BaseButton>
        </div>
      </div>
    </div>
  );
};
