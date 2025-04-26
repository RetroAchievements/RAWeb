import { zodResolver } from '@hookform/resolvers/zod';
import { router } from '@inertiajs/react';
import { useMutation } from '@tanstack/react-query';
import type { AxiosResponse } from 'axios';
import axios from 'axios';
import { useForm } from 'react-hook-form';
import { useTranslation } from 'react-i18next';
import { z } from 'zod';

import { toastMessage } from '@/common/components/+vendor/BaseToaster';
import { preProcessShortcodesInBody } from '@/common/utils/shortcodes/preProcessShortcodesInBody';

const formSchema = z.object({
  body: z.string().min(1).max(60_000),
});
type FormValues = z.infer<typeof formSchema>;

export function useUpsertPostForm(
  props: Partial<{ targetComment: App.Data.ForumTopicComment; targetTopic: App.Data.ForumTopic }>,
  initialValues: FormValues,
) {
  const { targetComment, targetTopic } = props;

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

      if (targetComment) {
        return axios.patch(
          route('api.forum-topic-comment.update', { comment: targetComment.id }),
          normalizedPayload,
        );
      }

      return axios.post(
        route('api.forum-topic-comment.create', { topic: targetTopic!.id }),
        normalizedPayload,
      );
    },
  });

  const onSubmit = (formValues: FormValues) => {
    toastMessage.promise(mutation.mutateAsync(formValues), {
      loading: targetComment ? t('Updating...') : t('Submitting...'),
      success: ({ data }: AxiosResponse<{ commentId: number }>) => {
        if (targetComment) {
          router.visit(
            route('forum-topic.show', {
              topic: targetComment.forumTopic!.id,
              comment: targetComment.id,
            }) + `#${targetComment.id}`,
          );

          return t('Updated.');
        }

        window.location.assign(
          route('forum-topic.show', {
            topic: targetTopic!.id,
            _query: { comment: data.commentId },
          }) +
            '#' +
            data.commentId,
        );

        return t('Submitted!');
      },
      error: t('Something went wrong.'),
    });
  };

  return { form, mutation, onSubmit };
}
