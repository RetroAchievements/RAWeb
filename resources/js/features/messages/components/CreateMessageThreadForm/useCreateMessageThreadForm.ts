import { zodResolver } from '@hookform/resolvers/zod';
import { router } from '@inertiajs/react';
import { useMutation } from '@tanstack/react-query';
import axios, { type AxiosError } from 'axios';
import { useForm } from 'react-hook-form';
import { useTranslation } from 'react-i18next';
import { route } from 'ziggy-js';
import { z } from 'zod';

import { toastMessage } from '@/common/components/+vendor/BaseToaster';
import { preProcessShortcodesInBody } from '@/common/utils/shortcodes/preProcessShortcodesInBody';

const formSchema = z.object({
  recipient: z.string().min(3).max(20),
  title: z.string().min(2).max(255),
  body: z.string().min(1).max(60_000),
});
type FormValues = z.infer<typeof formSchema>;

export function useCreateMessageThreadForm(
  defaultValues: Partial<FormValues>,
  senderUserDisplayName: string,
) {
  const { t } = useTranslation();

  const form = useForm<FormValues>({
    resolver: zodResolver(formSchema),
    defaultValues: {
      recipient: '',
      title: '',
      body: '',
      ...defaultValues,
    },
  });

  const mutation = useMutation({
    mutationFn: (formValues: FormValues) => {
      const normalizedPayload = {
        ...formValues,
        senderUserDisplayName,
        body: preProcessShortcodesInBody(formValues.body),
      };

      return axios.post<{ threadId: number }>(route('api.message.store'), normalizedPayload);
    },
  });

  const onSubmit = async (formValues: FormValues) => {
    await toastMessage.promise(mutation.mutateAsync(formValues), {
      loading: t('Submitting...'),
      success: ({ data }) => {
        router.visit(route('message-thread.show', { messageThread: data.threadId }));

        return t('Submitted!');
      },
      error: ({ response }: AxiosError<{ error: string }>) => {
        if (response?.data.error === 'muted_user') {
          return t('Muted users can only message team accounts.');
        }

        if (response?.data.error === 'cannot_message_user') {
          return t("This user isn't accepting messages right now.");
        }

        return t('Something went wrong.');
      },
    });
  };

  return { form, mutation, onSubmit };
}
