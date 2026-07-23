import { zodResolver } from '@hookform/resolvers/zod';
import { useForm } from 'react-hook-form';
import { useTranslation } from 'react-i18next';
import type { z } from 'zod';

import { toastMessage } from '@/common/components/+vendor/BaseToaster';
import type { LaravelValidationError } from '@/common/models';
import { useUpdateOAuthApplicationMutation } from '@/features/settings/hooks/mutations/useUpdateOAuthApplicationMutation';
import { buildOAuthApplicationFormSchema } from '@/features/settings/utils/buildOAuthApplicationFormSchema';

interface UseOAuthApplicationFormProps {
  application: App.Data.OAuthClient;
  onUpdated: () => void;
}

export function useOAuthApplicationForm({ application, onUpdated }: UseOAuthApplicationFormProps) {
  const { t } = useTranslation();

  const oauthApplicationFormSchema = buildOAuthApplicationFormSchema(t);
  type FormValues = z.infer<typeof oauthApplicationFormSchema>;

  const form = useForm<FormValues>({
    resolver: zodResolver(oauthApplicationFormSchema),
    defaultValues: {
      name: application.name,
      redirectUri: application.redirectUris[0]!,
    },
  });

  const mutation = useUpdateOAuthApplicationMutation();

  const onSubmit = (formValues: FormValues) => {
    toastMessage.promise(
      mutation.mutateAsync({
        clientId: application.id,
        payload: {
          name: formValues.name,
          redirectUris: [formValues.redirectUri],
        },
      }),
      {
        loading: t('Saving...'),
        success: () => {
          form.reset(formValues);
          onUpdated();

          return t('Application updated.');
        },
        error: ({ response }: LaravelValidationError) => {
          return response.data.message;
        },
      },
    );
  };

  return { form, mutation, onSubmit };
}
