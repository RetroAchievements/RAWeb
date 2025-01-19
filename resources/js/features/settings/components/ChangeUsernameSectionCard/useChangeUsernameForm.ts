import { zodResolver } from '@hookform/resolvers/zod';
import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { useAtom } from 'jotai';
import { useMemo } from 'react';
import { useForm } from 'react-hook-form';
import { useTranslation } from 'react-i18next';
import { z } from 'zod';

import { toastMessage } from '@/common/components/+vendor/BaseToaster';
import { usePageProps } from '@/common/hooks/usePageProps';
import type { LaravelValidationError } from '@/common/models';

import { requestedUsernameAtom } from '../../state/settings.atoms';

export function useChangeUsernameForm() {
  const { auth } = usePageProps();

  const { t } = useTranslation();

  const [requestedUsername, setRequestedUsername] = useAtom(requestedUsernameAtom);

  const usernameChangeFormSchema = useMemo(
    () =>
      z
        .object({
          newUsername: z
            .string()
            .min(4)
            .max(20)
            .regex(/^[\x20-\x7E]*$/, t('Must contain only ASCII characters.')),
          confirmUsername: z
            .string()
            .min(4)
            .max(20)
            .regex(/^[\x20-\x7E]*$/, t('Must contain only ASCII characters.')),
        })
        .refine((data) => data.newUsername === data.confirmUsername, {
          message: t('New usernames must match.'),
          path: ['confirmUsername'],
        })
        .refine((data) => data.newUsername !== auth!.user.displayName, {
          message: t('New username must be different from current username.'),
          path: ['newUsername'],
        }),
    [auth, t],
  );

  type FormValues = z.infer<typeof usernameChangeFormSchema>;

  const form = useForm<FormValues>({
    resolver: zodResolver(usernameChangeFormSchema),
    disabled: !!requestedUsername,
    defaultValues: {
      newUsername: '',
      confirmUsername: '',
    },
  });

  const mutation = useMutation({
    mutationFn: (formValues: FormValues) => {
      return axios.post(route('api.settings.username-change-request.store'), {
        newDisplayName: formValues.newUsername,
      });
    },
    onSuccess: (_, { newUsername }) => {
      setRequestedUsername(newUsername);
    },
  });

  const onSubmit = async (formValues: FormValues) => {
    const confirmationMessage = t(
      'You can only request a new username once every 30 days, even if your new username is not approved. Are you sure you want to do this?',
    );

    if (!confirm(confirmationMessage)) {
      return;
    }

    await toastMessage.promise(mutation.mutateAsync(formValues), {
      loading: t('Submitting username change request...'),
      success: t('Submitted username change request!'),
      error: ({ response }: LaravelValidationError) => {
        if (response.data.message.includes('already been taken')) {
          return t('This username is already taken.');
        }

        return t('Something went wrong.');
      },
    });
  };

  return { form, mutation, onSubmit };
}
