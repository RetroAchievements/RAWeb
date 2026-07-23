import { zodResolver } from '@hookform/resolvers/zod';
import { useForm } from 'react-hook-form';
import { useTranslation } from 'react-i18next';
import { z } from 'zod';

import { toastMessage } from '@/common/components/+vendor/BaseToaster';
import type { LaravelValidationError } from '@/common/models';
import { useCreateOAuthApplicationMutation } from '@/features/settings/hooks/mutations/useCreateOAuthApplicationMutation';
import { buildOAuthApplicationFormSchema } from '@/features/settings/utils/buildOAuthApplicationFormSchema';

interface UseOAuthRegistrationFormProps {
  onSuccess: (credentials: App.Data.OAuthClientCredentials) => void;
}

export function useOAuthRegistrationForm({ onSuccess }: UseOAuthRegistrationFormProps) {
  const { t } = useTranslation();

  const oauthRegistrationFormSchema = buildOAuthApplicationFormSchema(t).extend({
    isPublic: z.boolean(),
  });
  type FormValues = z.infer<typeof oauthRegistrationFormSchema>;

  const form = useForm<FormValues>({
    resolver: zodResolver(oauthRegistrationFormSchema),
    defaultValues: {
      isPublic: false,
      name: '',
      redirectUri: '',
    },
  });

  const mutation = useCreateOAuthApplicationMutation();

  const onSubmit = (formValues: FormValues) => {
    toastMessage.promise(
      mutation.mutateAsync({
        payload: {
          enableDeviceFlow: true,
          name: formValues.name,
          redirectUris: [formValues.redirectUri],
          type: formValues.isPublic ? 'public' : 'confidential',
        },
      }),
      {
        loading: t('Registering application...'),
        success: ({ data }) => {
          onSuccess(data);

          return t('Application registered.');
        },
        error: ({ response }: LaravelValidationError) => {
          return response.data.message;
        },
      },
    );
  };

  return { form, mutation, onSubmit };
}
