import { zodResolver } from '@hookform/resolvers/zod';
import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { useForm } from 'react-hook-form';
import { useTranslation } from 'react-i18next';
import { z } from 'zod';

import { toastMessage } from '@/common/components/+vendor/BaseToaster';
import { preProcessShortcodesInBody } from '@/features/forums/utils/preProcessShortcodesInBody';

const formSchema = z.object({
  body: z.string().min(1).max(60_000),
});

type FormValues = z.infer<typeof formSchema>;

export function useEditPostForm(comment: App.Data.ForumTopicComment, initialValues: FormValues) {
  const { t } = useTranslation();

  const form = useForm<FormValues>({
    resolver: zodResolver(formSchema),
    defaultValues: initialValues,
  });

  const mutation = useMutation({
    mutationFn: (payload: FormValues) => {
      const normalizedPayload: FormValues = {
        ...payload,
        body: preProcessShortcodesInBody(payload.body),
      };

      return axios.patch(
        route('api.forum-topic-comment.update', { comment: comment.id }),
        normalizedPayload,
      );
    },
  });

  const onSubmit = (formValues: FormValues) => {
    toastMessage.promise(mutation.mutateAsync(formValues), {
      loading: t('Updating...'),
      success: () => {
        window.location.assign(
          `/viewtopic.php?t=${comment.forumTopic!.id}&c=${comment.id}#${comment.id}`,
        );

        return t('Updated.');
      },
      error: t('Something went wrong.'),
    });
  };

  return { form, mutation, onSubmit };
}
