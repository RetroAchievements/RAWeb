import { zodResolver } from '@hookform/resolvers/zod';
import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { useMemo } from 'react';
import { useForm } from 'react-hook-form';
import { useTranslation } from 'react-i18next';
import { z } from 'zod';

import { toastMessage } from '@/common/components/+vendor/BaseToaster';
import type { LaravelValidationError } from '@/common/models';

export function useChangePasswordForm() {
  const { t } = useTranslation();

  const changePasswordFormSchema = useMemo(
    () =>
      z
        .object({
          currentPassword: z.string().min(1, { message: t('Required') }),
          newPassword: z.string().min(8, { message: t('Must be at least 8 characters.') }),
          confirmPassword: z.string().min(8, { message: t('Must be at least 8 characters.') }),
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

  const mutation = useMutation({
    mutationFn: (formValues: FormValues) => {
      return axios.put(route('api.settings.password.update'), formValues);
    },
  });

  const onSubmit = (formValues: FormValues) => {
    toastMessage.promise(mutation.mutateAsync(formValues), {
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
