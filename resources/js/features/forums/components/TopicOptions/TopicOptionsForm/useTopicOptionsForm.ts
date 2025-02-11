import { zodResolver } from '@hookform/resolvers/zod';
import { router } from '@inertiajs/react';
import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { useForm } from 'react-hook-form';
import { useTranslation } from 'react-i18next';
import { z } from 'zod';

import { toastMessage } from '@/common/components/+vendor/BaseToaster';

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

  const mutation = useMutation({
    mutationFn: (payload: FormValues) => {
      return axios.put(route('api.forum-topic.update', { topic: topic.id }), payload);
    },

    onSuccess: () => {
      router.reload();
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
