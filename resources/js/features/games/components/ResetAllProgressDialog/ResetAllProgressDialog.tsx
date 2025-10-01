import { router } from '@inertiajs/react';
import { useAtom } from 'jotai';
import { type FC, useState } from 'react';
import { Trans, useTranslation } from 'react-i18next';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import { BaseCheckbox } from '@/common/components/+vendor/BaseCheckbox';
import {
  BaseDialog,
  BaseDialogClose,
  BaseDialogContent,
  BaseDialogDescription,
  BaseDialogFooter,
  BaseDialogHeader,
  BaseDialogTitle,
} from '@/common/components/+vendor/BaseDialog';
import { BaseLabel } from '@/common/components/+vendor/BaseLabel';
import { toastMessage } from '@/common/components/+vendor/BaseToaster';
import { useResetGameProgressMutation } from '@/common/hooks/mutations/useResetGameProgressMutation';
import { usePageProps } from '@/common/hooks/usePageProps';
import { isResetAllProgressDialogOpenAtom } from '@/features/games/state/games.atoms';

export const ResetAllProgressDialog: FC = () => {
  const { backingGame } = usePageProps<App.Platform.Data.GameShowPageProps>();
  const { t } = useTranslation();

  const [isChecked, setIsChecked] = useState(false);

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

  const handleOpenChange = () => {
    setIsChecked(false);
    setIsResetAllProgressDialogOpen(false);
  };

  return (
    <BaseDialog open={isResetAllProgressDialogOpen} onOpenChange={handleOpenChange}>
      <BaseDialogContent>
        <BaseDialogHeader>
          <BaseDialogTitle>{t('Reset Progress')}</BaseDialogTitle>

          <BaseDialogDescription className="text-left">
            <Trans
              i18nKey="You are about to permanently delete all your unlocks for this achievement set. <1>This cannot be reversed.</1>"
              components={{
                1: <span className="font-bold" />,
              }}
            />
          </BaseDialogDescription>
        </BaseDialogHeader>

        <BaseLabel className="flex cursor-pointer items-center gap-1.5">
          <BaseCheckbox
            checked={isChecked}
            onCheckedChange={(checked) => setIsChecked(!!checked)}
          />

          {t('I understand. I want to reset my progress.')}
        </BaseLabel>

        <BaseDialogFooter className="mt-4">
          <BaseDialogClose asChild>
            <BaseButton variant="link" size="sm">
              {t('Cancel')}
            </BaseButton>
          </BaseDialogClose>

          <BaseButton
            variant="destructive"
            size="sm"
            onClick={handleConfirmClick}
            disabled={!isChecked || mutation.isPending}
          >
            {t('Reset Progress')}
          </BaseButton>
        </BaseDialogFooter>
      </BaseDialogContent>
    </BaseDialog>
  );
};
