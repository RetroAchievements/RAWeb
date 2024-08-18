import { zodResolver } from '@hookform/resolvers/zod';
import { usePage } from '@inertiajs/react';
import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { useForm } from 'react-hook-form';
import { route } from 'ziggy-js';
import { z } from 'zod';

import { toastMessage } from '@/common/components/+vendor/BaseToaster';

import type { SettingsPageProps } from '../../models';

const changeEmailAddressFormSchema = z
  .object({
    newEmail: z.string().email(),
    confirmEmail: z.string().email(),
  })
  .refine((data) => data.newEmail === data.confirmEmail, {
    message: 'Email addresses must match.',
    path: ['confirmEmail'],
  });

type FormValues = z.infer<typeof changeEmailAddressFormSchema>;

export function useChangeEmailAddressForm(props: {
  setCurrentEmailAddress: React.Dispatch<React.SetStateAction<string>>;
}) {
  const {
    props: { auth },
  } = usePage<SettingsPageProps>();

  const form = useForm<FormValues>({
    resolver: zodResolver(changeEmailAddressFormSchema),
    defaultValues: {
      newEmail: '',
      confirmEmail: '',
    },
  });

  const mutation = useMutation({
    mutationFn: (formValues: FormValues) => {
      return axios.put(route('settings.email.update'), formValues);
    },
    onSuccess: () => {
      props.setCurrentEmailAddress(form.getValues().newEmail);
    },
  });

  const onSubmit = (formValues: FormValues) => {
    const confirmationMessage = auth?.user.roles.length
      ? 'Changing your email address will revoke your privileges and you will need to have them restored by staff. Are you sure you want to do this?'
      : 'Are you sure you want to change your email address?';

    if (!confirm(confirmationMessage)) {
      return;
    }

    toastMessage.promise(mutation.mutateAsync(formValues), {
      loading: 'Changing email address...',
      success: 'Changed email address!',
      error: 'Something went wrong.',
    });
  };

  return { form, mutation, onSubmit };
}
