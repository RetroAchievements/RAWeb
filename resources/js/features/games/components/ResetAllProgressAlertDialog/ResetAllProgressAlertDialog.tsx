import { router } from '@inertiajs/react';
import { useAtom } from 'jotai';
import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import {
  BaseAlertDialog,
  BaseAlertDialogCancel,
  BaseAlertDialogContent,
  BaseAlertDialogDescription,
  BaseAlertDialogFooter,
  BaseAlertDialogHeader,
  BaseAlertDialogTitle,
} from '@/common/components/+vendor/BaseAlertDialog';
import { BaseButton } from '@/common/components/+vendor/BaseButton';
import { toastMessage } from '@/common/components/+vendor/BaseToaster';
import { useResetGameProgressMutation } from '@/common/hooks/mutations/useResetGameProgressMutation';
import { usePageProps } from '@/common/hooks/usePageProps';
import { isResetAllProgressDialogOpenAtom } from '@/features/games/state/games.atoms';

export const ResetAllProgressAlertDialog: FC = () => {
  const { backingGame } = usePageProps<App.Platform.Data.GameShowPageProps>();
  const { t } = useTranslation();

  // The dialog is mounted way higher than the tooltip.
  // This prevents the dialog from unmounting when the tooltip closes.
  const [isResetAllProgressDialogOpen, setIsResetAllProgressDialogOpen] = useAtom(
    isResetAllProgressDialogOpenAtom,
  );

  const mutation = useResetGameProgressMutation();

  const handleConfirmClick = async () => {
    await toastMessage.promise(mutation.mutateAsync({ payload: { game: backingGame.id } }), {
      loading: t('Resetting progress...'),
      success: () => {
        setIsResetAllProgressDialogOpen(false);
        router.reload();

        return t('Progress was reset successfully.');
      },
      error: t('Something went wrong.'),
    });
  };

  return (
    <BaseAlertDialog
      open={isResetAllProgressDialogOpen}
      onOpenChange={setIsResetAllProgressDialogOpen}
    >
      <BaseAlertDialogContent>
        <BaseAlertDialogHeader>
          <BaseAlertDialogTitle>{t('Are you sure?')}</BaseAlertDialogTitle>

          <BaseAlertDialogDescription>
            {t(
              'This will remove all your unlocked achievements for the game. This cannot be reversed.',
            )}
          </BaseAlertDialogDescription>
        </BaseAlertDialogHeader>

        <BaseAlertDialogFooter>
          <BaseAlertDialogCancel>{t('Nevermind')}</BaseAlertDialogCancel>

          <BaseButton type="button" variant="destructive" onClick={handleConfirmClick}>
            {t('Continue')}
          </BaseButton>
        </BaseAlertDialogFooter>
      </BaseAlertDialogContent>
    </BaseAlertDialog>
  );
};
