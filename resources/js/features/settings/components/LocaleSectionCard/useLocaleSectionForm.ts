import { zodResolver } from '@hookform/resolvers/zod';
import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { useForm } from 'react-hook-form';
import { useTranslation } from 'react-i18next';
import { z } from 'zod';

import { toastMessage } from '@/common/components/+vendor/BaseToaster';

const localeFormSchema = z.object({
  locale: z.string(),
});

type FormValues = z.infer<typeof localeFormSchema>;

export function useLocaleSectionForm(initialValues: FormValues) {
  const { t } = useTranslation();

  const form = useForm<FormValues>({
    resolver: zodResolver(localeFormSchema),
    defaultValues: initialValues,
  });

  const mutation = useMutation({
    mutationFn: (formValues: FormValues) => {
      return axios.put(route('api.settings.locale.update'), formValues);
    },
  });

  const onSubmit = (formValues: FormValues) => {
    toastMessage.promise(mutation.mutateAsync(formValues), {
      loading: t('Updating...'),
      success: () => {
        setTimeout(() => {
          window.location.reload();
        }, 1000);

        return t('Updated.');
      },
      error: t('Something went wrong.'),
    });
  };

  return { form, mutation, onSubmit };
}
