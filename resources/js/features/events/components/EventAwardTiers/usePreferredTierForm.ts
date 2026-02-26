import { zodResolver } from '@hookform/resolvers/zod';
import { useForm } from 'react-hook-form';
import { useTranslation } from 'react-i18next';
import { z } from 'zod';

import { toastMessage } from '@/common/components/+vendor/BaseToaster';
import { useUpdateEventAwardTierPreferenceMutation } from '@/features/events/hooks/mutations/useUpdateEventAwardTierPreferenceMutation';

const formSchema = z.object({
  tierIndex: z.number().int().min(0),
});
type FormValues = z.infer<typeof formSchema>;

interface UsePreferredTierFormProps {
  eventId: number;
  initialTierIndex: number;
  onSubmitSuccess: () => void;
}

export function usePreferredTierForm({
  eventId,
  initialTierIndex,
  onSubmitSuccess,
}: UsePreferredTierFormProps) {
  const { t } = useTranslation();

  const form = useForm<FormValues>({
    resolver: zodResolver(formSchema),
    defaultValues: {
      tierIndex: initialTierIndex,
    },
  });

  const mutation = useUpdateEventAwardTierPreferenceMutation();

  const onSubmit = async (formValues: FormValues) => {
    await toastMessage.promise(
      mutation.mutateAsync({
        payload: { eventId, tierIndex: formValues.tierIndex },
      }),
      {
        loading: t('Saving...'),
        success: () => {
          onSubmitSuccess();

          return t('Saved!');
        },
        error: t('Something went wrong.'),
      },
    );
  };

  return { form, mutation, onSubmit };
}
