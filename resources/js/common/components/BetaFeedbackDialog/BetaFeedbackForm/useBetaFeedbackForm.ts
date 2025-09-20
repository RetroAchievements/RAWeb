import { zodResolver } from '@hookform/resolvers/zod';
import { useForm } from 'react-hook-form';
import { useTranslation } from 'react-i18next';
import { z } from 'zod';

import { useSubmitBetaFeedbackMutation } from '@/common/hooks/mutations/useSubmitBetaFeedbackMutation';

import { toastMessage } from '../../+vendor/BaseToaster';

const formSchema = z.object({
  rating: z.enum(['1', '2', '3', '4', '5']),
  positiveFeedback: z.string(),
  negativeFeedback: z.string(),
});
type FormValues = z.infer<typeof formSchema>;

export function useBetaFeedbackForm(betaName: string, onSubmitSuccess: () => void) {
  const { t } = useTranslation();

  const form = useForm<FormValues>({
    resolver: zodResolver(formSchema),
    defaultValues: {
      positiveFeedback: '',
      negativeFeedback: '',
    },
  });

  const mutation = useSubmitBetaFeedbackMutation();

  const onSubmit = async (formValues: FormValues) => {
    await toastMessage.promise(
      mutation.mutateAsync({
        payload: {
          betaName,
          negativeFeedback: formValues.negativeFeedback,
          positiveFeedback: formValues.positiveFeedback,
          rating: Number(formValues.rating),
        },
      }),
      {
        loading: t('Submitting...'),
        success: () => {
          onSubmitSuccess();

          return t('Submitted!');
        },
        error: t('Something went wrong.'),
      },
    );
  };

  return { form, mutation, onSubmit };
}
