import { zodResolver } from '@hookform/resolvers/zod';
import { router } from '@inertiajs/react';
import { useForm } from 'react-hook-form';
import { useTranslation } from 'react-i18next';
import type { z } from 'zod';

import { toastMessage } from '@/common/components/+vendor/BaseToaster';
import { convertObjectToWebsitePrefs } from '@/common/utils/convertObjectToWebsitePrefs';
import { convertWebsitePrefsToObject } from '@/common/utils/convertWebsitePrefsToObject';

import { useUpdateUserPreferencesMutation } from '../../hooks/mutations/useUpdateUserPreferencesMutation';
import { websitePrefsFormSchema } from '../../utils/websitePrefsFormSchema';

export type FormValues = z.infer<typeof websitePrefsFormSchema>;

export function useNotificationsSectionForm(websitePrefs: number) {
  const { t } = useTranslation();

  const form = useForm<FormValues>({
    resolver: zodResolver(websitePrefsFormSchema),
    defaultValues: convertWebsitePrefsToObject(websitePrefs),
  });

  const mutation = useUpdateUserPreferencesMutation();

  const onSubmit = (formValues: FormValues) => {
    const newWebsitePrefs = convertObjectToWebsitePrefs(formValues);

    toastMessage.promise(mutation.mutateAsync(newWebsitePrefs), {
      loading: t('Updating...'),
      success: () => {
        router.reload();

        return t('Updated.');
      },
      error: t('Something went wrong.'),
    });
  };

  return { form, mutation, onSubmit };
}
