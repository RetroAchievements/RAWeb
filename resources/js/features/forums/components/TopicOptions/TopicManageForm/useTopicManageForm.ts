import { zodResolver } from '@hookform/resolvers/zod';
import { useForm } from 'react-hook-form';
import { useTranslation } from 'react-i18next';
import { z } from 'zod';

import { toastMessage } from '@/common/components/+vendor/BaseToaster';
import { useGateForumTopicMutation } from '@/features/forums/hooks/mutations/useGateForumTopicMutation';

const formSchema = z.object({
  permissions: z.coerce.number().int().min(0).max(4), // legacy permissions
});
type FormValues = z.infer<typeof formSchema>;

export function useTopicManageForm(topic: App.Data.ForumTopic) {
  const { t } = useTranslation();

  const form = useForm<FormValues>({
    resolver: zodResolver(formSchema),
    defaultValues: {
      permissions: topic.requiredPermissions!,
    },
  });

  const mutation = useGateForumTopicMutation();

  const onSubmit = (formValues: FormValues) => {
    toastMessage.promise(
      mutation.mutateAsync({
        topic: topic.id,
        payload: { permissions: formValues.permissions },
      }),
      {
        loading: t('Submitting...'),
        success: t('Submitted!'),
        error: t('Something went wrong.'),
      },
    );
  };

  return { form, mutation, onSubmit };
}
