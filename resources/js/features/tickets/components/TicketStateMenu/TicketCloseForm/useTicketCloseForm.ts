import { zodResolver } from '@hookform/resolvers/zod';
import type { AxiosError } from 'axios';
import { useForm } from 'react-hook-form';
import { useTranslation } from 'react-i18next';
import { z } from 'zod';

import { toastMessage } from '@/common/components/+vendor/BaseToaster';
import { usePageProps } from '@/common/hooks/usePageProps';
import { buildCommentBodySchema } from '@/common/utils/buildCommentBodySchema';

import { useUpdateTicketStateMutation } from '../../../hooks/mutations/useUpdateTicketStateMutation';
import type { TicketCloseAnswer } from '../../../models';
import type { TicketCloseAction } from '../../../utils/ticketCloseReasonLabels';

export interface FormValues {
  answer: TicketCloseAnswer | null;
  action: TicketCloseAction | null;
  note: string;
}

interface UseTicketCloseFormProps {
  onSubmitSuccess: () => void;
}

export function useTicketCloseForm({ onSubmitSuccess }: UseTicketCloseFormProps) {
  const { ticket } = usePageProps<App.Platform.Data.TicketShowPageProps>();
  const { t } = useTranslation();

  const formSchema = z
    .object({
      answer: z.custom<TicketCloseAnswer | null>(),
      action: z
        .custom<TicketCloseAction | null>()
        .refine((action): action is TicketCloseAction => action !== null, {
          message: t('Required'),
        }),
      note: z
        .string()
        .trim()
        .transform((trimmedNote) => trimmedNote || null)
        .pipe(buildCommentBodySchema(t).nullable()),
    })
    .refine(({ action, note }) => action !== 'closed-other' || note !== null, {
      message: t('Required'),
      path: ['note'],
    });

  const form = useForm<FormValues, unknown, z.output<typeof formSchema>>({
    resolver: zodResolver(formSchema),
    mode: 'onTouched', // don't throw validation errors on a half-typed note
    defaultValues: { answer: null, action: null, note: '' },
  });

  const mutation = useUpdateTicketStateMutation();

  const onSubmit = (formValues: z.output<typeof formSchema>) => {
    toastMessage.promise(
      mutation.mutateAsync({
        ticketId: ticket.id,
        payload: { action: formValues.action, note: formValues.note },
      }),
      {
        loading: t('Updating...'),
        success: () => {
          onSubmitSuccess();

          return t('Updated.');
        },
        error: (error: AxiosError<{ message?: string }>) => {
          if (error.response?.status === 403) {
            return t('This ticket changed since you opened it. Reload the page and try again.'); // this should almost never happen
          }

          return error.response?.data?.message ?? t('Something went wrong.');
        },
      },
    );
  };

  return { form, mutation, onSubmit };
}
