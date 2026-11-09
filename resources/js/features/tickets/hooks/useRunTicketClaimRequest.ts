import { router } from '@inertiajs/react';
import { useTranslation } from 'react-i18next';

import { toastMessage } from '@/common/components/+vendor/BaseToaster';

export function useRunTicketClaimRequest() {
  const { t } = useTranslation();

  return (request: Promise<unknown>) => {
    toastMessage.promise(
      request.finally(() => {
        router.reload();
      }),
      {
        loading: t('Updating...'),
        success: t('Updated.'),
        error: t('Something went wrong.'),
      },
    );
  };
}
