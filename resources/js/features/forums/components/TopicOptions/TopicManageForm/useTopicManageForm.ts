import { zodResolver } from '@hookform/resolvers/zod';
import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { useForm } from 'react-hook-form';
import { useTranslation } from 'react-i18next';
import { route } from 'ziggy-js';
import { z } from 'zod';

import { toastMessage } from '@/common/components/+vendor/BaseToaster';

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

  const mutation = useMutation({
    mutationFn: (payload: FormValues) => {
      return axios.put(route('api.forum-topic.gate', { topic: topic.id }), payload);
    },
  });

  const onSubmit = (formValues: FormValues) => {
    toastMessage.promise(mutation.mutateAsync(formValues), {
      loading: t('Submitting...'),
      success: t('Submitted!'),
      error: t('Something went wrong.'),
    });
  };

  return { form, mutation, onSubmit };
}
