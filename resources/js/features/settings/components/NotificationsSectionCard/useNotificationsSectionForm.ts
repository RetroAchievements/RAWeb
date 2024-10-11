import { zodResolver } from '@hookform/resolvers/zod';
import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { useEffect } from 'react';
import { useForm } from 'react-hook-form';
import type { z } from 'zod';

import { toastMessage } from '@/common/components/+vendor/BaseToaster';
import { convertObjectToWebsitePrefs } from '@/common/utils/convertObjectToWebsitePrefs';
import { convertWebsitePrefsToObject } from '@/common/utils/convertWebsitePrefsToObject';

import { websitePrefsFormSchema } from '../../utils/websitePrefsFormSchema';

export type FormValues = z.infer<typeof websitePrefsFormSchema>;

export function useNotificationsSectionForm(
  websitePrefs: number,
  onUpdateWebsitePrefs: (newWebsitePrefs: number) => unknown,
) {
  const { t } = useLaravelReactI18n();

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

  const mutation = useMutation({
    mutationFn: (websitePrefs: number) => {
      return axios.put(route('api.settings.preferences.update'), { websitePrefs });
    },
  });

  const onSubmit = (formValues: FormValues) => {
    const newWebsitePrefs = convertObjectToWebsitePrefs(formValues);

    toastMessage.promise(mutation.mutateAsync(newWebsitePrefs), {
      loading: t('Updating...'),
      success: () => {
        onUpdateWebsitePrefs(newWebsitePrefs);

        return t('Updated.');
      },
      error: t('Something went wrong.'),
    });
  };

  return { form, mutation, onSubmit };
}
