import { zodResolver } from '@hookform/resolvers/zod';
import { useAtom } from 'jotai';
import { useState } from 'react';
import { useForm } from 'react-hook-form';
import { useTranslation } from 'react-i18next';
import { z } from 'zod';

import { toastMessage } from '@/common/components/+vendor/BaseToaster';
import { usePageProps } from '@/common/hooks/usePageProps';
import type { LaravelValidationError } from '@/common/models';
import { useCreateNameChangeRequestMutation } from '@/features/settings/hooks/mutations/useCreateNameChangeRequestMutation';

import { requestedUsernameAtom } from '../../state/settings.atoms';

export function useChangeUsernameForm() {
  const { auth } = usePageProps();
  const { t } = useTranslation();

  const [requestedUsername, setRequestedUsername] = useAtom(requestedUsernameAtom);

  const usernameChangeFormSchema = z
    .object({
      newUsername: z
        .string()
        .min(4)
        .max(20)
        .regex(/^[a-zA-Z0-9]+$/, t('Must only contain unaccented letters and numbers.')),
      confirmUsername: z
        .string()
        .min(4)
        .max(20)
        .regex(/^[a-zA-Z0-9]+$/, t('Must only contain unaccented letters and numbers.')),
    })
    .refine((data) => data.newUsername === data.confirmUsername, {
      message: t('New usernames must match.'),
      path: ['confirmUsername'],
    })
    .refine((data) => data.newUsername !== auth!.user.displayName, {
      message: t('New username must be different from current username.'),
      path: ['newUsername'],
    });

  type FormValues = z.infer<typeof usernameChangeFormSchema>;

  const form = useForm<FormValues>({
    resolver: zodResolver(usernameChangeFormSchema),
    disabled: !!requestedUsername,
    defaultValues: {
      newUsername: '',
      confirmUsername: '',
    },
  });

  const mutation = useCreateNameChangeRequestMutation();

  const [isConfirmDialogOpen, setIsConfirmDialogOpen] = useState(false);
  const [pendingUsername, setPendingUsername] = useState('');

  const isOnlyCapitalizationChange = (newUsername: string) =>
    auth!.user.displayName.toLowerCase() === newUsername.toLowerCase();

  const submitUsernameChange = async (newUsername: string) => {
    const payload = { newDisplayName: newUsername };

    await toastMessage.promise(mutation.mutateAsync({ payload }), {
      loading: t('Submitting username change request...'),
      success: () => {
        setIsConfirmDialogOpen(false);

        if (isOnlyCapitalizationChange(newUsername)) {
          window.location.reload();

          return t('Updated.');
        }

        setRequestedUsername(newUsername);

        return t('Submitted username change request!');
      },
      error: ({ response }: LaravelValidationError) => {
        if (response.data.message.includes('already been taken')) {
          return t('This username is already taken.');
        }

        if (response.data.message.includes('not available')) {
          return t('This username is not available.');
        }

        return t('Something went wrong.');
      },
    });
  };

  const onSubmit = async (formValues: FormValues) => {
    // Bypass the confirm dialog on capitalization-only changes.
    if (isOnlyCapitalizationChange(formValues.newUsername)) {
      await submitUsernameChange(formValues.newUsername);

      return;
    }

    setPendingUsername(formValues.newUsername);
    setIsConfirmDialogOpen(true);
  };

  const onConfirmUsernameChange = async () => {
    await submitUsernameChange(pendingUsername);
  };

  return {
    form,
    isConfirmDialogOpen,
    mutation,
    onConfirmUsernameChange,
    onSubmit,
    pendingUsername,
    setIsConfirmDialogOpen,
  };
}
