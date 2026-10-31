import { zodResolver } from '@hookform/resolvers/zod';
import { router } from '@inertiajs/react';
import { useForm } from 'react-hook-form';
import { useTranslation } from 'react-i18next';
import { route } from 'ziggy-js';
import { z } from 'zod';

import { toastMessage } from '@/common/components/+vendor/BaseToaster';
import { usePageProps } from '@/common/hooks/usePageProps';
import { useCreateTicketMutation } from '@/features/achievements/hooks/mutations/useCreateTicketMutation';
import { shouldTicketTypeOfferManualUnlock } from '@/features/achievements/utils/shouldTicketTypeOfferManualUnlock';

export interface CreateAchievementTicketFormValues {
  /** @see TicketType.php */
  issue: 'DidNotTrigger' | 'TriggeredAtWrongTime' | 'NetworkIssue';
  mode: 'hardcore' | 'casual';
  description: string;

  core?: string;
  emulator?: string;
  emulatorVersion?: string;
  hash?: string;
}

function buildCreateAchievementTicketFormSchema(
  hasEmulatorOptions: boolean,
  hasGameHashOptions: boolean,
): z.ZodType<CreateAchievementTicketFormValues> {
  return z.object({
    issue: z.enum(['DidNotTrigger', 'TriggeredAtWrongTime', 'NetworkIssue']),
    emulator: hasEmulatorOptions ? z.string().min(1) : z.string().optional(),
    emulatorVersion: z.string().optional(),
    core: z.string().optional(),
    mode: z.enum(['hardcore', 'casual']),
    hash: hasGameHashOptions ? z.string().min(1) : z.string().optional(),
    description: z.string().min(25, { message: 'Please be more detailed in your description.' }),
  });
}

export function useCreateAchievementTicketForm(
  initialValues: Partial<CreateAchievementTicketFormValues>,
) {
  const {
    achievement,
    emulators,
    gameHashes,
    ziggy: { query },
  } = usePageProps<App.Platform.Data.CreateAchievementTicketPageProps>();
  const { t } = useTranslation();

  const form = useForm<CreateAchievementTicketFormValues>({
    resolver: zodResolver(
      buildCreateAchievementTicketFormSchema(emulators.length > 0, gameHashes.length > 0),
    ),
    defaultValues: initialValues,
  });

  const mutation = useCreateTicketMutation();

  const onSubmit = async (formValues: CreateAchievementTicketFormValues) => {
    const ticketType = getTicketTypeFromIssue(formValues.issue);

    toastMessage.promise(
      mutation.mutateAsync({
        payload: {
          ticketableModel: 'achievement',
          ticketableId: achievement.id,
          mode: formValues.mode,
          issue: ticketType,
          description: formValues.description,
          emulator: formValues.emulator ?? null,
          emulatorVersion: formValues.emulatorVersion?.trim() ? formValues.emulatorVersion : null,
          core: formValues.core,
          gameHashId: formValues.hash ? Number(formValues.hash) : null,
          extra: query.extra ?? null,
        },
      }),
      {
        loading: t('Submitting...'),
        success: (submitResponse) => {
          if (!shouldTicketTypeOfferManualUnlock(ticketType)) {
            router.visit(route('ticket.show', { ticket: submitResponse.data.ticketId }));
          }

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
