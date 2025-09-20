import { zodResolver } from '@hookform/resolvers/zod';
import { router } from '@inertiajs/react';
import { useForm } from 'react-hook-form';
import { useTranslation } from 'react-i18next';
import { z } from 'zod';

import { toastMessage } from '@/common/components/+vendor/BaseToaster';
import { useUpdateForumTopicMutation } from '@/features/forums/hooks/mutations/useUpdateForumTopicMutation';

const formSchema = z.object({
  title: z.string().min(2).max(255),
});
type FormValues = z.infer<typeof formSchema>;

export function useTopicOptionsForm(topic: App.Data.ForumTopic) {
  const { t } = useTranslation();

  const form = useForm<FormValues>({
    resolver: zodResolver(formSchema),
    defaultValues: {
      title: topic.title,
    },
  });

  const mutation = useUpdateForumTopicMutation();

  const onSubmit = (formValues: FormValues) => {
    toastMessage.promise(mutation.mutateAsync({ topic: topic.id, payload: formValues }), {
      loading: t('Submitting...'),
      success: () => {
        router.reload();

        return t('Submitted!');
      },
      error: t('Something went wrong.'),
    });
  };

  return { form, mutation, onSubmit };
}
