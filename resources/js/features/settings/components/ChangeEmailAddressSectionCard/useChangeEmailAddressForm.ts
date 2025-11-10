import { zodResolver } from '@hookform/resolvers/zod';
import { useMemo } from 'react';
import { useForm } from 'react-hook-form';
import { useTranslation } from 'react-i18next';
import { z } from 'zod';

import { toastMessage } from '@/common/components/+vendor/BaseToaster';
import { usePageProps } from '@/common/hooks/usePageProps';
import { useChangeEmailAddressMutation } from '@/features/settings/hooks/mutations/useChangeEmailAddressMutation';

export function useChangeEmailAddressForm(props: {
  setCurrentEmailAddress: React.Dispatch<React.SetStateAction<string>>;
}) {
  const { auth } = usePageProps<App.Community.Data.UserSettingsPageProps>();
  const { t } = useTranslation();

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

  const mutation = useChangeEmailAddressMutation();

  const onSubmit = (formValues: FormValues) => {
    const confirmationMessage = auth?.user.roles.length
      ? t(
          'Changing your email address will revoke your privileges and you will need to have them restored by staff. Are you sure you want to do this?',
        )
      : t('Are you sure you want to change your email address?');

    if (!confirm(confirmationMessage)) {
      return;
    }

    toastMessage.promise(mutation.mutateAsync({ payload: formValues }), {
      loading: t('Changing email address...'),
      success: () => {
        props.setCurrentEmailAddress(form.getValues().newEmail);

        return t('Changed email address!');
      },
      error: (error) => {
        const response = error?.response;

        if (response?.data?.message?.includes('provider is not allowed')) {
          return t('This email provider is not allowed.');
        }

        return t('Something went wrong.');
      },
    });
  };

  return { form, mutation, onSubmit };
}
