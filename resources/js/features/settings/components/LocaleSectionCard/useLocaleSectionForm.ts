import { zodResolver } from '@hookform/resolvers/zod';
import { useEffect, useRef } from 'react';
import { useForm } from 'react-hook-form';
import { useTranslation } from 'react-i18next';
import { z } from 'zod';

import { toastMessage } from '@/common/components/+vendor/BaseToaster';
import { useUpdateLocaleMutation } from '@/features/settings/hooks/mutations/useUpdateLocaleMutation';

const localeFormSchema = z.object({
  locale: z.string(),
});

type FormValues = z.infer<typeof localeFormSchema>;

export function useLocaleSectionForm(initialValues: FormValues) {
  const { t } = useTranslation();

  const reloadTimeoutRef = useRef<NodeJS.Timeout | null>(null);

  const form = useForm<FormValues>({
    resolver: zodResolver(localeFormSchema),
    defaultValues: initialValues,
  });

  useEffect(() => {
    return () => {
      if (reloadTimeoutRef.current) {
        clearTimeout(reloadTimeoutRef.current);
      }
    };
  }, []);

  const mutation = useUpdateLocaleMutation();

  const onSubmit = (formValues: FormValues) => {
    toastMessage.promise(mutation.mutateAsync({ payload: formValues }), {
      loading: t('Updating...'),
      success: () => {
        reloadTimeoutRef.current = setTimeout(() => {
          window.location.reload();
        }, 1000);

        return t('Updated.');
      },
      error: t('Something went wrong.'),
    });
  };

  return { form, mutation, onSubmit };
}
