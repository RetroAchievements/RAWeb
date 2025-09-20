import { zodResolver } from '@hookform/resolvers/zod';
import { router } from '@inertiajs/react';
import type { AxiosResponse } from 'axios';
import { useForm } from 'react-hook-form';
import { useTranslation } from 'react-i18next';
import { route } from 'ziggy-js';
import { z } from 'zod';

import { toastMessage } from '@/common/components/+vendor/BaseToaster';
import { preProcessShortcodesInBody } from '@/common/utils/shortcodes/preProcessShortcodesInBody';
import { useCreateForumTopicCommentMutation } from '@/features/forums/hooks/mutations/useCreateForumTopicCommentMutation';
import { useUpdateForumTopicCommentMutation } from '@/features/forums/hooks/mutations/useUpdateForumTopicCommentMutation';

const formSchema = z.object({
  body: z.string().min(1).max(60_000),
  postAsUserId: z.string().optional(),
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

  const updateMutation = useUpdateForumTopicCommentMutation();
  const createMutation = useCreateForumTopicCommentMutation();

  const onSubmit = (formValues: FormValues) => {
    const normalizedPayload = {
      body: preProcessShortcodesInBody(formValues.body),
      postAsUserId: formValues.postAsUserId === 'self' ? null : Number(formValues.postAsUserId),
    };

    const mutationPromise = targetComment
      ? updateMutation.mutateAsync({ comment: targetComment.id, payload: normalizedPayload })
      : createMutation.mutateAsync({ topic: targetTopic!.id, payload: normalizedPayload });

    toastMessage.promise(mutationPromise, {
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

        router.visit(
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

  return { form, onSubmit, mutation: targetComment ? updateMutation : createMutation };
}
