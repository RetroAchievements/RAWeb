import { router } from '@inertiajs/react';
import { useAtom } from 'jotai';
import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import {
  BaseDialog,
  BaseDialogClose,
  BaseDialogContent,
  BaseDialogDescription,
  BaseDialogFooter,
  BaseDialogHeader,
  BaseDialogTitle,
} from '@/common/components/+vendor/BaseDialog';
import { toastMessage } from '@/common/components/+vendor/BaseToaster';
import { usePageProps } from '@/common/hooks/usePageProps';

import { useUpdateAchievementMutation } from '../../hooks/mutations/useUpdateAchievementMutation';
import { isUpdatePromotedStatusDialogOpenAtom } from '../../state/achievements.atoms';

export const UpdatePromotedStatusDialog: FC = () => {
  const { achievement } = usePageProps<App.Platform.Data.AchievementShowPageProps>();
  const { t } = useTranslation();

  const [isDialogOpen, setIsDialogOpen] = useAtom(isUpdatePromotedStatusDialogOpenAtom);
  const mutation = useUpdateAchievementMutation();

  const handleConfirmClick = () => {
    toastMessage.promise(
      mutation.mutateAsync({
        achievementId: achievement.id,
        payload: { isPromoted: !achievement.isPromoted },
      }),
      {
        loading: achievement.isPromoted ? t('Demoting...') : t('Promoting...'),
        success: () => {
          setIsDialogOpen(false);
          router.reload();

          return achievement.isPromoted ? t('Demoted!') : t('Promoted!');
        },
        error: t('Something went wrong.'),
      },
    );
  };

  return (
    <BaseDialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
      <BaseDialogContent>
        <BaseDialogHeader>
          <BaseDialogTitle>{t('Confirm')}</BaseDialogTitle>

          <BaseDialogDescription className="text-left">
            {achievement.isPromoted
              ? t('Are you sure you want to demote this achievement?')
              : t('Are you sure you want to promote this achievement?')}
          </BaseDialogDescription>
        </BaseDialogHeader>

        <BaseDialogFooter className="mt-4">
          <BaseDialogClose asChild>
            <BaseButton variant="link" size="sm">
              {t('Cancel')}
            </BaseButton>
          </BaseDialogClose>

          <BaseButton
            variant={achievement.isPromoted ? 'destructive' : 'default'}
            size="sm"
            onClick={handleConfirmClick}
            disabled={mutation.isPending}
          >
            {achievement.isPromoted ? t('Demote') : t('Promote')}
          </BaseButton>
        </BaseDialogFooter>
      </BaseDialogContent>
    </BaseDialog>
  );
};
