import { zodResolver } from '@hookform/resolvers/zod';
import { router } from '@inertiajs/react';
import { useForm } from 'react-hook-form';
import { useTranslation } from 'react-i18next';
import { route } from 'ziggy-js';
import { z } from 'zod';

import { toastMessage } from '@/common/components/+vendor/BaseToaster';
import { usePageProps } from '@/common/hooks/usePageProps';
import { preProcessShortcodesInBody } from '@/common/utils/shortcodes/preProcessShortcodesInBody';
import { useCreateForumTopicMutation } from '@/features/forums/hooks/mutations/useCreateForumTopicMutation';

const formSchema = z.object({
  title: z.string().min(2).max(255),
  body: z.string().min(1).max(60_000),
  postAsUserId: z.string().optional(),
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
      postAsUserId: 'self',
    },
  });

  const mutation = useCreateForumTopicMutation();

  const onSubmit = (formValues: FormValues) => {
    toastMessage.promise(
      mutation.mutateAsync({
        category: forum.category!.id,
        forum: forum.id,
        payload: {
          title: formValues.title,
          body: preProcessShortcodesInBody(formValues.body),
          postAsUserId: formValues.postAsUserId === 'self' ? null : Number(formValues.postAsUserId),
        },
      }),
      {
        loading: t('Submitting...'),
        success: ({ data }) => {
          router.visit(route('forum-topic.show', { topic: data.newTopicId }));

          return t('Submitted!');
        },
        error: t('Something went wrong.'),
      },
    );
  };

  return { form, mutation, onSubmit };
}
