import { zodResolver } from '@hookform/resolvers/zod';
import { router } from '@inertiajs/react';
import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { useForm } from 'react-hook-form';
import { useTranslation } from 'react-i18next';
import { z } from 'zod';

import { toastMessage } from '@/common/components/+vendor/BaseToaster';
import { usePageProps } from '@/common/hooks/usePageProps';
import { preProcessShortcodesInBody } from '@/common/utils/shortcodes/preProcessShortcodesInBody';

const formSchema = z.object({
  title: z.string().min(2).max(255),
  body: z.string().min(1).max(60_000),
});
type FormValues = z.infer<typeof formSchema>;

export function useCreateTopicForm() {
  const { forum } = usePageProps<App.Data.CreateForumTopicPageProps>();

  const { t } = useTranslation();

  const form = useForm<FormValues>({
    resolver: zodResolver(formSchema),
    defaultValues: {
      title: '',
      body: '',
    },
  });

  const mutation = useMutation({
    mutationFn: (payload: FormValues) => {
      const normalizedPayload: FormValues = {
        ...payload,
        body: preProcessShortcodesInBody(payload.body),
      };

      return axios.post<{ success: boolean; newTopicId: number }>(
        route('api.forum-topic.store', { category: forum.category!.id, forum: forum.id }),
        normalizedPayload,
      );
    },
  });

  const onSubmit = (formValues: FormValues) => {
    toastMessage.promise(mutation.mutateAsync(formValues), {
      loading: t('Submitting...'),
      success: ({ data }) => {
        router.visit(route('forum-topic.show', { topic: data.newTopicId }));

        return t('Submitted!');
      },
      error: t('Something went wrong.'),
    });
  };

  return { form, mutation, onSubmit };
}
