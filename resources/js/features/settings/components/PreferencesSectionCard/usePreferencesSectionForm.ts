import { zodResolver } from '@hookform/resolvers/zod';
import { router } from '@inertiajs/react';
import { useEffect } from 'react';
import { useForm } from 'react-hook-form';
import { useTranslation } from 'react-i18next';
import type { z } from 'zod';

import { toastMessage } from '@/common/components/+vendor/BaseToaster';
import { convertObjectToWebsitePrefs } from '@/common/utils/convertObjectToWebsitePrefs';
import { convertWebsitePrefsToObject } from '@/common/utils/convertWebsitePrefsToObject';
import { useUpdatePreferencesMutation } from '@/features/settings/hooks/mutations/useUpdatePreferencesMutation';

import { websitePrefsFormSchema } from '../../utils/websitePrefsFormSchema';

export type FormValues = z.infer<typeof websitePrefsFormSchema>;

export function usePreferencesSectionForm(websitePrefs: number) {
  const { t } = useTranslation();

  const form = useForm<FormValues>({
    resolver: zodResolver(websitePrefsFormSchema),
    defaultValues: convertWebsitePrefsToObject(websitePrefs),
  });

  useEffect(() => {
    const prefsAsObject = convertWebsitePrefsToObject(websitePrefs);
    for (const [key, value] of Object.entries(prefsAsObject)) {
      form.setValue(key as keyof FormValues, value);
    }
  }, [form, websitePrefs]);

  const mutation = useUpdatePreferencesMutation();

  const onSubmit = (formValues: FormValues) => {
    const newWebsitePrefs = convertObjectToWebsitePrefs(formValues);

    toastMessage.promise(
      mutation.mutateAsync({ payload: { preferencesBitfield: newWebsitePrefs } }),
      {
        loading: t('Updating...'),
        success: () => {
          router.reload();

          return t('Updated.');
        },
        error: t('Something went wrong.'),
      },
    );
  };

  return { form, mutation, onSubmit };
}
