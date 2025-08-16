import { useTranslation } from 'react-i18next';

import { toastMessage } from '@/common/components/+vendor/BaseToaster';
import { useCompleteClaimMutation } from '@/features/games/hooks/mutations/useCompleteClaimMutation';
import { useCreateClaimMutation } from '@/features/games/hooks/mutations/useCreateClaimMutation';
import { useDropClaimMutation } from '@/features/games/hooks/mutations/useDropClaimMutation';

export type ClaimActionType = 'create' | 'drop' | 'extend' | 'complete';

interface ClaimActionMessages {
  loading: string;
  success: string;
  error: string;
}

export const useClaimActions = () => {
  const { t } = useTranslation();

  const completeClaimMutation = useCompleteClaimMutation();
  const createClaimMutation = useCreateClaimMutation();
  const dropClaimMutation = useDropClaimMutation();

  const actionMessages: Record<ClaimActionType, ClaimActionMessages> = {
    create: {
      loading: t('Creating new claim...'),
      success: t('Claimed!'),
      error: t('Something went wrong.'),
    },
    drop: {
      loading: t('Dropping claim...'),
      success: t('Dropped!'),
      error: t('Something went wrong.'),
    },
    extend: {
      loading: t('Extending claim...'),
      success: t('Extended!'),
      error: t('Something went wrong.'),
    },
    complete: {
      loading: t('Completing claim...'),
      success: t('Completed!'),
      error: t('Something went wrong.'),
    },
  };

  const executeCreateClaim = async (gameId: number) => {
    return toastMessage.promise(createClaimMutation.mutateAsync({ gameId }), actionMessages.create);
  };

  const executeDropClaim = async (gameId: number) => {
    return toastMessage.promise(dropClaimMutation.mutateAsync({ gameId }), actionMessages.drop);
  };

  const executeExtendClaim = async (gameId: number) => {
    // Extension uses the create mutation endpoint.
    return toastMessage.promise(createClaimMutation.mutateAsync({ gameId }), actionMessages.extend);
  };

  const executeCompleteClaim = async (claimId: number) => {
    return toastMessage.promise(
      completeClaimMutation.mutateAsync({ claimId }),
      actionMessages.complete,
    );
  };

  return {
    executeCreateClaim,
    executeDropClaim,
    executeExtendClaim,
    executeCompleteClaim,
    mutations: {
      createClaimMutation,
      dropClaimMutation,
      completeClaimMutation,
    },
  };
};
