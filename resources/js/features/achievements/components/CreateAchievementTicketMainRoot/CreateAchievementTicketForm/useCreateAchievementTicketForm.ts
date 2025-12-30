import { zodResolver } from '@hookform/resolvers/zod';
import { useForm } from 'react-hook-form';
import { useTranslation } from 'react-i18next';
import { route } from 'ziggy-js';
import { z } from 'zod';

import { toastMessage } from '@/common/components/+vendor/BaseToaster';
import { usePageProps } from '@/common/hooks/usePageProps';
import { useCreateTicketMutation } from '@/features/achievements/hooks/mutations/useCreateTicketMutation';

const createAchievementTicketFormSchema = z.object({
  /** @see TicketType.php */
  issue: z.enum(['DidNotTrigger', 'TriggeredAtWrongTime', 'NetworkIssue']),
  emulator: z.string().min(1),
  emulatorVersion: z.string().optional(),
  core: z.string().optional(),
  mode: z.enum(['hardcore', 'softcore']),
  hash: z.string().min(1),
  description: z.string().min(25, { message: 'Please be more detailed in your description.' }),
});

export type CreateAchievementTicketFormValues = z.infer<typeof createAchievementTicketFormSchema>;

export function useCreateAchievementTicketForm(
  initialValues: Partial<CreateAchievementTicketFormValues>,
) {
  const {
    achievement,
    ziggy: { query },
  } = usePageProps<App.Platform.Data.CreateAchievementTicketPageProps>();
  const { t } = useTranslation();

  const form = useForm<CreateAchievementTicketFormValues>({
    resolver: zodResolver(createAchievementTicketFormSchema),
    defaultValues: initialValues,
  });

  const mutation = useCreateTicketMutation();

  const onSubmit = async (formValues: CreateAchievementTicketFormValues) => {
    toastMessage.promise(
      mutation.mutateAsync({
        payload: {
          ticketableModel: 'achievement',
          ticketableId: achievement.id,
          mode: formValues.mode,
          issue: getTicketTypeFromIssue(formValues.issue),
          description: formValues.description,
          emulator: formValues.emulator,
          emulatorVersion: formValues.emulatorVersion?.trim() ? formValues.emulatorVersion : null,
          core: formValues.core,
          gameHashId: Number(formValues.hash),
          extra: query.extra ?? null,
        },
      }),
      {
        loading: t('Submitting...'),
        success: (submitResponse) => {
          setTimeout(() => {
            const { ticketId } = submitResponse.data;

            // TODO use router.visit after migrating this page to React
            window.location.href = route('ticket.show', { ticket: ticketId });
          }, 1000);

          return t('Submitted!');
        },
        error: t('Something went wrong.'),
      },
    );
  };

  return { form, mutation, onSubmit };
}

function getTicketTypeFromIssue(
  issue: CreateAchievementTicketFormValues['issue'],
): App.Community.Enums.TicketType {
  if (issue === 'DidNotTrigger') {
    return 'did_not_trigger';
  }

  return 'triggered_at_wrong_time';
}
