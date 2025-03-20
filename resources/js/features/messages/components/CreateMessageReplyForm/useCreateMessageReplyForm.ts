import { zodResolver } from '@hookform/resolvers/zod';
import { useMutation } from '@tanstack/react-query';
import axios, { type AxiosError } from 'axios';
import { useForm } from 'react-hook-form';
import { useTranslation } from 'react-i18next';
import { z } from 'zod';

import { toastMessage } from '@/common/components/+vendor/BaseToaster';
import { usePageProps } from '@/common/hooks/usePageProps';
import { preProcessShortcodesInBody } from '@/common/utils/shortcodes/preProcessShortcodesInBody';

const formSchema = z.object({
  body: z.string().min(1).max(2000),
});
type FormValues = z.infer<typeof formSchema>;

export function useCreateMessageReplyForm() {
  const { messageThread, paginatedMessages } =
    usePageProps<App.Community.Data.MessageThreadShowPageProps>();

  const { t } = useTranslation();

  const form = useForm<FormValues>({
    resolver: zodResolver(formSchema),
    defaultValues: { body: '' },
  });

  const mutation = useMutation({
    mutationFn: (formValues: FormValues) => {
      const normalizedPayload = {
        // TODO "thread_id" -> "threadId"
        thread_id: messageThread.id,
        body: preProcessShortcodesInBody(formValues.body),
      };

      return axios.post(route('api.message.store'), normalizedPayload);
    },
  });

  const onSubmit = async (formValues: FormValues) => {
    await toastMessage.promise(mutation.mutateAsync(formValues), {
      loading: t('Submitting...'),
      success: () => {
        setTimeout(() => {
          window.location.assign(
            route('message-thread.show', {
              messageThread: messageThread.id,
              _query: { page: paginatedMessages.lastPage },
            }),
          );
        }, 1000);

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
