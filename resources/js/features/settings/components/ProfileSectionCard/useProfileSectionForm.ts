import { zodResolver } from '@hookform/resolvers/zod';
import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { useForm } from 'react-hook-form';
import { useTranslation } from 'react-i18next';
import { z } from 'zod';

import { toastMessage } from '@/common/components/+vendor/BaseToaster';

const profileFormSchema = z.object({
  motto: z.string().max(50),
  userWallActive: z.boolean(),
  visibleRoleId: z
    .string() // The incoming value is a string from a select field.
    .nullable()
    .transform((val) => Number(val)) // We need to convert it to a numeric ID.
    .pipe(z.number().nullable())
    .optional(),
});

export type FormValues = z.infer<typeof profileFormSchema>;

export function useProfileSectionForm(initialValues: FormValues) {
  const { t } = useTranslation();

  const form = useForm<FormValues>({
    resolver: zodResolver(profileFormSchema),
    defaultValues: initialValues,
  });

  const mutation = useMutation({
    mutationFn: (formValues: FormValues) => {
      const payload = { ...formValues };
      if (!payload.visibleRoleId) {
        delete payload.visibleRoleId;
      }

      return axios.put(route('api.settings.profile.update'), payload);
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
