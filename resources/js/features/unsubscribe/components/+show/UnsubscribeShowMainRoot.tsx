import type { FC } from 'react';
import { useState } from 'react';
import { useTranslation } from 'react-i18next';

import { toastMessage } from '@/common/components/+vendor/BaseToaster';
import { usePageProps } from '@/common/hooks/usePageProps';

import { useUndoUnsubscribeMutation } from '../../hooks/mutations/useUndoUnsubscribeMutation';
import { UnsubscribeErrorCard } from '../UnsubscribeErrorCard';
import { UnsubscribeSuccessCard } from '../UnsubscribeSuccessCard';
import { UnsubscribeUndoSuccessCard } from '../UnsubscribeUndoSuccessCard';

export const UnsubscribeShowMainRoot: FC = () => {
  const { success, undoToken } = usePageProps<App.Community.Data.UnsubscribeShowPageProps>();
  const { t } = useTranslation();

  const [undoSuccess, setUndoSuccess] = useState(false);

  const undoMutation = useUndoUnsubscribeMutation();

  const handleUndo = async () => {
    try {
      await undoMutation.mutateAsync(undoToken!);
      setUndoSuccess(true);
    } catch {
      toastMessage.error(t('Something went wrong.'));
    }
  };

  return (
    <div className="container mx-auto max-w-2xl px-4 py-12">
      {success && undoSuccess ? <UnsubscribeUndoSuccessCard /> : null}

      {success && undoToken && !undoSuccess ? (
        <UnsubscribeSuccessCard onUndo={handleUndo} isMutationPending={undoMutation.isPending} />
      ) : null}

      {!success ? <UnsubscribeErrorCard /> : null}
    </div>
  );
};
