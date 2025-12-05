import { zodResolver } from '@hookform/resolvers/zod';
import { useForm } from 'react-hook-form';
import { useTranslation } from 'react-i18next';
import { z } from 'zod';

import { toastMessage } from '@/common/components/+vendor/BaseToaster';
import { usePageProps } from '@/common/hooks/usePageProps';
import { useUpdateGameAchievementSetPreferencesMutation } from '@/features/games/hooks/mutations/useUpdateGameAchievementSetPreferencesMutation';

const formSchema = z.object({
  /**
   * "123": true,     // Set ID 123 is opted in
   * "456": false,    // Set ID 456 is opted out
   */
  preferences: z.record(z.string(), z.boolean()),
});
type FormValues = z.infer<typeof formSchema>;

interface UseSubsetConfigurationFormProps {
  configurableSets: App.Platform.Data.GameAchievementSet[];
  onSubmitSuccess: () => void;
}

export function useSubsetConfigurationForm({
  configurableSets,
  onSubmitSuccess,
}: UseSubsetConfigurationFormProps) {
  const { auth, userGameAchievementSetPreferences } =
    usePageProps<App.Platform.Data.GameShowPageProps>();
  const { t } = useTranslation();

  const isGloballyOptedOut = !!auth?.user.preferences.isGloballyOptedOutOfSubsets;

  const form = useForm<FormValues>({
    resolver: zodResolver(formSchema),

    /**
     * If a user has a local preference, we'll always respect the value of the preference.
     *
     * Otherwise, set each toggle switch's default value based on whether the user is
     * globally opted in or opted out to subsets.
     */
    defaultValues: buildDefaultValues(
      configurableSets,
      userGameAchievementSetPreferences,
      isGloballyOptedOut,
    ),
  });

  const mutation = useUpdateGameAchievementSetPreferencesMutation();

  const onSubmit = async (formValues: FormValues) => {
    const preferencesToUpdate: Array<{ gameAchievementSetId: number; optedIn: boolean }> = [];

    // In the request payload, we only want to include values that have actually changed.
    for (const set of configurableSets) {
      const formValue = formValues.preferences[String(set.id)];
      const existingPreference = userGameAchievementSetPreferences[set.id];

      // Core sets always default to opted in.
      const isCoreSet = set.type === 'core';
      const defaultValue = isCoreSet ? true : !isGloballyOptedOut;

      const hasChanged = existingPreference
        ? existingPreference.optedIn !== formValue
        : formValue !== defaultValue;

      if (hasChanged) {
        preferencesToUpdate.push({
          gameAchievementSetId: set.id,
          optedIn: formValue,
        });
      }
    }

    await toastMessage.promise(
      mutation.mutateAsync({ payload: { preferences: preferencesToUpdate } }),
      {
        loading: t('Saving...'),
        success: () => {
          onSubmitSuccess();

          return t('Saved!');
        },
        error: t('Something went wrong.'),
      },
    );
  };

  return { form, mutation, onSubmit };
}

function buildDefaultValues(
  configurableSets: App.Platform.Data.GameAchievementSet[],
  userPreferences: Record<number, App.Platform.Data.UserGameAchievementSetPreference>,
  isGloballyOptedOut: boolean,
): FormValues {
  const defaultValues: FormValues = {
    preferences: {},
  };

  for (const set of configurableSets) {
    const preference = userPreferences[set.id];

    if (preference) {
      // The user has a local preference. Use it.
      defaultValues.preferences[String(set.id)] = preference.optedIn;
    } else {
      // The user doesn't have a local preference.
      // Core sets are always opted in by default (which matches our back-end behavior).
      // Non-core sets follow the global preference.
      const isCoreSet = set.type === 'core';
      defaultValues.preferences[String(set.id)] = isCoreSet ? true : !isGloballyOptedOut;
    }
  }

  return defaultValues;
}
