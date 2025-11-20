import { zodResolver } from '@hookform/resolvers/zod';
import { router } from '@inertiajs/react';
import { type AxiosError } from 'axios';
import { useForm } from 'react-hook-form';
import { useTranslation } from 'react-i18next';
import { route } from 'ziggy-js';
import { z } from 'zod';

import { toastMessage } from '@/common/components/+vendor/BaseToaster';
import { preProcessShortcodesInBody } from '@/common/utils/shortcodes/preProcessShortcodesInBody';
import { useCreateMessageThreadMutation } from '@/features/messages/hooks/mutations/useCreateMessageThreadMutation';

const formSchema = z.object({
  recipient: z.string().min(3).max(20),
  title: z.string().min(2).max(255),
  body: z.string().min(1).max(60_000),
});
type FormValues = z.infer<typeof formSchema>;

export function useCreateMessageThreadForm(
  defaultValues: Partial<FormValues>,
  senderUserDisplayName: string,
  reportableType?: App.Community.Enums.DiscordReportableType | null,
  reportableId?: number | null,
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

  const mutation = useCreateMessageThreadMutation();

  const onSubmit = async (formValues: FormValues) => {
    const normalizedPayload = {
      ...formValues,
      senderUserDisplayName,
      body: preProcessShortcodesInBody(formValues.body),
      ...(reportableType && reportableId ? { rType: reportableType, rId: reportableId } : {}),
    };

    await toastMessage.promise(mutation.mutateAsync({ payload: normalizedPayload }), {
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
