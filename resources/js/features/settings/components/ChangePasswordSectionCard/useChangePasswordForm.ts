import { zodResolver } from '@hookform/resolvers/zod';
import { useMemo } from 'react';
import { useForm } from 'react-hook-form';
import { useTranslation } from 'react-i18next';
import { route } from 'ziggy-js';
import { z } from 'zod';

import { toastMessage } from '@/common/components/+vendor/BaseToaster';
import type { LaravelValidationError } from '@/common/models';
import { useChangePasswordMutation } from '@/features/settings/hooks/mutations/useChangePasswordMutation';

export function useChangePasswordForm() {
  const { t } = useTranslation();

  const changePasswordFormSchema = useMemo(
    () =>
      z
        .object({
          currentPassword: z.string().min(1, { message: t('Required') }),
          newPassword: z
            .string()
            .min(10, { message: t('Must be at least {{val, number}} characters.', { val: 10 }) }),
          confirmPassword: z
            .string()
            .min(10, { message: t('Must be at least {{val, number}} characters.', { val: 10 }) }),
        })
        .refine((data) => data.newPassword === data.confirmPassword, {
          message: t('Passwords must match.'),
          path: ['confirmPassword'],
        }),
    [t],
  );

  type FormValues = z.infer<typeof changePasswordFormSchema>;

  const form = useForm<FormValues>({
    resolver: zodResolver(changePasswordFormSchema),
    defaultValues: {
      confirmPassword: '',
      currentPassword: '',
      newPassword: '',
    },
  });

  const mutation = useChangePasswordMutation();

  const onSubmit = (formValues: FormValues) => {
    toastMessage.promise(mutation.mutateAsync({ payload: formValues }), {
      loading: t('Changing password...'),
      success: () => {
        window.location.href = route('login');

        return '';
      },
      error: ({ response }: LaravelValidationError) => {
        return response.data.message;
      },
    });
  };

  return { form, mutation, onSubmit };
}
