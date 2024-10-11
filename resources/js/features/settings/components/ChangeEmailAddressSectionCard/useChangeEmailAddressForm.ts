import { zodResolver } from '@hookform/resolvers/zod';
import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import { useMemo } from 'react';
import { useForm } from 'react-hook-form';
import { z } from 'zod';

import { toastMessage } from '@/common/components/+vendor/BaseToaster';
import { usePageProps } from '@/common/hooks/usePageProps';

export function useChangeEmailAddressForm(props: {
  setCurrentEmailAddress: React.Dispatch<React.SetStateAction<string>>;
}) {
  const { auth } = usePageProps<App.Community.Data.UserSettingsPageProps>();

  const { t } = useLaravelReactI18n();

  const changeEmailAddressFormSchema = useMemo(
    () =>
      z
        .object({
          newEmail: z.string().email(),
          confirmEmail: z.string().email(),
        })
        .refine((data) => data.newEmail === data.confirmEmail, {
          message: t('Email addresses must match.'),
          path: ['confirmEmail'],
        }),
    [t],
  );

  type FormValues = z.infer<typeof changeEmailAddressFormSchema>;

  const form = useForm<FormValues>({
    resolver: zodResolver(changeEmailAddressFormSchema),
    defaultValues: {
      newEmail: '',
      confirmEmail: '',
    },
  });

  const mutation = useMutation({
    mutationFn: (formValues: FormValues) => {
      return axios.put(route('api.settings.email.update'), formValues);
    },
    onSuccess: () => {
      props.setCurrentEmailAddress(form.getValues().newEmail);
    },
  });

  const onSubmit = (formValues: FormValues) => {
    const confirmationMessage = auth?.user.roles.length
      ? t(
          'Changing your email address will revoke your privileges and you will need to have them restored by staff. Are you sure you want to do this?',
        )
      : t('Are you sure you want to change your email address?');

    if (!confirm(confirmationMessage)) {
      return;
    }

    toastMessage.promise(mutation.mutateAsync(formValues), {
      loading: t('Changing email address...'),
      success: t('Changed email address!'),
      error: t('Something went wrong.'),
    });
  };

  return { form, mutation, onSubmit };
}
