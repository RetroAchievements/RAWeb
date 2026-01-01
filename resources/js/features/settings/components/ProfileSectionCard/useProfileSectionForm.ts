import { zodResolver } from '@hookform/resolvers/zod';
import { useForm } from 'react-hook-form';
import { useTranslation } from 'react-i18next';
import { z } from 'zod';

import { toastMessage } from '@/common/components/+vendor/BaseToaster';
import { useUpdateProfileMutation } from '@/features/settings/hooks/mutations/useUpdateProfileMutation';

const profileFormSchema = z.object({
  isUserWallActive: z.boolean(),
  motto: z.string().max(50),
  visibleRoleId: z.coerce
    .number() // The incoming value is a string from a select field.
    .nullable()
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

  const mutation = useUpdateProfileMutation();

  const onSubmit = (formValues: FormValues) => {
    const payload = { ...formValues };
    if (!payload.visibleRoleId) {
      delete payload.visibleRoleId;
    }

    toastMessage.promise(mutation.mutateAsync({ payload }), {
      loading: t('Updating...'),
      success: t('Updated.'),
      error: t('Something went wrong.'),
    });
  };

  return { form, mutation, onSubmit };
}
