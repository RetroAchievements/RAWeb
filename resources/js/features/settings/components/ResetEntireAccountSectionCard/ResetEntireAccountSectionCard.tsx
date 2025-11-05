import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { type FC } from 'react';
import { useTranslation } from 'react-i18next';
import { route } from 'ziggy-js';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import { toastMessage } from '@/common/components/+vendor/BaseToaster';

import { SectionStandardCard } from '../SectionStandardCard';

export const ResetEntireAccountSectionCard: FC = () => {
  const { t } = useTranslation();

  const mutation = useMutation({
    mutationFn: () => {
      return axios.delete(route('api.user.progress.destroy'));
    },
  });

  const handleResetEntireAccount = () => {
    if (
      !confirm(
        t(
          'Are you sure you want to reset your ENTIRE account? This will delete ALL achievements, badges, leaderboard entries, and points. This action cannot be undone.',
        ),
      )
    ) {
      return;
    }

    if (
      !confirm(
        t(
          'This is your final warning. Your entire account progress will be permanently deleted. Are you absolutely sure?',
        ),
      )
    ) {
      return;
    }

    toastMessage.promise(mutation.mutateAsync(), {
      loading: t('Resetting entire account...'),
      success: () => {
        setTimeout(() => {
          window.location.reload();
        }, 2000);

        return t('Your entire account progress has been reset.');
      },
      error: t('Something went wrong.'),
    });
  };

  return (
    <SectionStandardCard t_headingLabel={t('Reset Entire Account')}>
      <div className="flex flex-col gap-4 rounded-md border border-red-600/30 bg-red-950/20 p-4">
        <div className="flex flex-col gap-2">
          <p className="font-bold text-red-500">
            {t(
              'This will permanently delete ALL of your achievements, badges, leaderboard entries, and reset all points to zero. This action cannot be undone.',
            )}
          </p>
        </div>

        <div className="flex justify-end">
          <BaseButton
            variant="destructive"
            onClick={handleResetEntireAccount}
            disabled={mutation.isPending}
          >
            {t('Reset Entire Account')}
          </BaseButton>
        </div>
      </div>
    </SectionStandardCard>
  );
};
