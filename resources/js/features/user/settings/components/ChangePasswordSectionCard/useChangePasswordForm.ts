import { zodResolver } from '@hookform/resolvers/zod';
import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { useForm } from 'react-hook-form';
import { z } from 'zod';

import { toastMessage } from '@/common/components/+vendor/BaseToaster';
import type { LaravelValidationError } from '@/common/models';

const changePasswordFormSchema = z
  .object({
    currentPassword: z.string().min(1, { message: 'Required' }),
    newPassword: z.string().min(8, { message: 'Must be at least 8 characters' }),
    confirmPassword: z.string().min(8, { message: 'Must be at least 8 characters' }),
  })
  .refine((data) => data.newPassword === data.confirmPassword, {
    message: 'Passwords must match.',
    path: ['confirmPassword'],
  });

type FormValues = z.infer<typeof changePasswordFormSchema>;

export function useChangePasswordForm() {
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
      return axios.put(route('settings.password.update'), formValues);
    },
  });

  const onSubmit = (formValues: FormValues) => {
    toastMessage.promise(mutation.mutateAsync(formValues), {
      loading: 'Changing password...',
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
