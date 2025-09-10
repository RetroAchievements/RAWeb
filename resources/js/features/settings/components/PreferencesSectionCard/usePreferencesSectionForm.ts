import { zodResolver } from '@hookform/resolvers/zod';
import { useEffect } from 'react';
import { useForm } from 'react-hook-form';
import { useTranslation } from 'react-i18next';
import type { z } from 'zod';

import { toastMessage } from '@/common/components/+vendor/BaseToaster';
import { convertObjectToWebsitePrefs } from '@/common/utils/convertObjectToWebsitePrefs';
import { convertWebsitePrefsToObject } from '@/common/utils/convertWebsitePrefsToObject';

import { useToggleBetaFeaturesMutation } from '../../hooks/mutations/useToggleBetaFeaturesMutation';
import { useUpdateUserPreferencesMutation } from '../../hooks/mutations/useUpdateUserPreferencesMutation';
import { websitePrefsFormSchema } from '../../utils/websitePrefsFormSchema';

export type FormValues = z.infer<typeof websitePrefsFormSchema>;

export function usePreferencesSectionForm(
  websitePrefs: number,
  onUpdateWebsitePrefs: (newWebsitePrefs: number) => unknown,
  hasBetaFeatures: boolean,
) {
  const { t } = useTranslation();

  const form = useForm<FormValues>({
    resolver: zodResolver(websitePrefsFormSchema),
    defaultValues: {
      ...convertWebsitePrefsToObject(websitePrefs),
      hasBetaFeatures,
    },
  });

  useEffect(() => {
    const prefsAsObject = convertWebsitePrefsToObject(websitePrefs);
    for (const [key, value] of Object.entries(prefsAsObject)) {
      form.setValue(key as keyof FormValues, value);
    }
  }, [form, websitePrefs, hasBetaFeatures]);

  const updateUserPreferencesMutation = useUpdateUserPreferencesMutation();
  const toggleBetaFeaturesMutation = useToggleBetaFeaturesMutation();

  const onSubmit = async (formValues: FormValues) => {
    const promises: Promise<unknown>[] = [];

    // Extract hasBetaFeatures from the form values and handle preferences separately.
    const { hasBetaFeatures: formHasBetaFeatures, ...prefsValues } = formValues;
    const newWebsitePrefs = convertObjectToWebsitePrefs(prefsValues);

    // Always update preferences.
    promises.push(updateUserPreferencesMutation.mutateAsync(newWebsitePrefs));

    // Only toggle beta features if it changed.
    if (formHasBetaFeatures !== hasBetaFeatures) {
      promises.push(toggleBetaFeaturesMutation.mutateAsync());
    }

    await toastMessage.promise(Promise.all(promises), {
      loading: t('Updating...'),
      success: () => {
        onUpdateWebsitePrefs(newWebsitePrefs);

        return t('Updated.');
      },
      error: t('Something went wrong.'),
    });
  };

  // Return the combined pending state if either mutation is pending.
  const isPending = updateUserPreferencesMutation.isPending || toggleBetaFeaturesMutation.isPending;

  return { form, onSubmit, mutation: { isPending } };
}
