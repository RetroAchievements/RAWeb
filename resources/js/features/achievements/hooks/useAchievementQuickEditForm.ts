import { zodResolver } from '@hookform/resolvers/zod';
import { router } from '@inertiajs/react';
import { useSetAtom } from 'jotai';
import { useForm } from 'react-hook-form';
import { useTranslation } from 'react-i18next';
import { z } from 'zod';

import { toastMessage } from '@/common/components/+vendor/BaseToaster';
import { usePageProps } from '@/common/hooks/usePageProps';

import { isEditModeAtom } from '../state/achievements.atoms';
import { useUpdateAchievementMutation } from './mutations/useUpdateAchievementMutation';

const achievementQuickEditFormSchema = z.object({
  description: z.string().max(255),
  points: z.number().int().min(0).max(100),
  title: z.string().min(1).max(64),
  type: z.enum(['none', 'missable', 'progression', 'win_condition']),
});
export type AchievementQuickEditFormValues = z.infer<typeof achievementQuickEditFormSchema>;

export function useAchievementQuickEditForm(initialValues: AchievementQuickEditFormValues) {
  const { achievement } = usePageProps<App.Platform.Data.AchievementShowPageProps>();
  const { t } = useTranslation();

  const setIsEditMode = useSetAtom(isEditModeAtom);
  const mutation = useUpdateAchievementMutation();

  const form = useForm<AchievementQuickEditFormValues>({
    resolver: zodResolver(achievementQuickEditFormSchema),
    defaultValues: initialValues,
  });

  const onSubmit = (formValues: AchievementQuickEditFormValues) => {
    // Compare against initial values rather than using dirtyFields.
    // handleSubmit is async, so form.reset() from exiting edit mode
    // can clear dirtyFields before this callback runs.
    const payload: Record<string, unknown> = {};
    let hasChanges = false;

    for (const key of Object.keys(formValues) as (keyof AchievementQuickEditFormValues)[]) {
      if (formValues[key] === initialValues[key]) {
        continue;
      }

      const value = formValues[key];

      // Map 'none' to null for the type field.
      payload[key] = key === 'type' && value === 'none' ? null : value;
      hasChanges = true;
    }

    if (!hasChanges) {
      return;
    }

    toastMessage.promise(mutation.mutateAsync({ achievementId: achievement.id, payload }), {
      loading: t('Saving...'),
      success: () => {
        router.reload({ onFinish: () => setIsEditMode(false) });

        return t('Saved!');
      },
      error: t('Something went wrong.'),
    });
  };

  return { form, mutation, onSubmit };
}
