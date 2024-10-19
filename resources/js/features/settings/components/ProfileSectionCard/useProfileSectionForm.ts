import { zodResolver } from '@hookform/resolvers/zod';
import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { useForm } from 'react-hook-form';
import { z } from 'zod';

import { toastMessage } from '@/common/components/+vendor/BaseToaster';

const profileFormSchema = z.object({
  motto: z.string().max(50),
  userWallActive: z.boolean(),
});

type FormValues = z.infer<typeof profileFormSchema>;

export function useProfileSectionForm(initialValues: FormValues) {
  const { t } = useLaravelReactI18n();

  const form = useForm<FormValues>({
    resolver: zodResolver(profileFormSchema),
    defaultValues: initialValues,
  });

  const mutation = useMutation({
    mutationFn: (formValues: FormValues) => {
      return axios.put(route('api.settings.profile.update'), formValues);
    },
  });

  const onSubmit = (formValues: FormValues) => {
    toastMessage.promise(mutation.mutateAsync(formValues), {
      loading: t('Updating...'),
      success: t('Updated.'),
      error: t('Something went wrong.'),
    });
  };

  return { form, mutation, onSubmit };
}
