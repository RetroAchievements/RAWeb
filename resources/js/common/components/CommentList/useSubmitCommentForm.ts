import { zodResolver } from '@hookform/resolvers/zod';
import { useEffect } from 'react';
import { useForm } from 'react-hook-form';
import { useTranslation } from 'react-i18next';
import { route } from 'ziggy-js';
import { z } from 'zod';

import { toastMessage } from '@/common/components/+vendor/BaseToaster';
import { useSubmitCommentMutation } from '@/common/hooks/mutations/useSubmitCommentMutation';
import { useFormDraft } from '@/common/hooks/useFormDraft';
import { loadDraft } from '@/common/utils/loadDraft';

import { useCommentListContext } from './CommentListContext';

interface UseSubmitCommentFormProps {
  commentableId: number | string;
  commentableType: App.Community.Enums.CommentableType;

  onSubmitSuccess?: () => void;
}

export function useSubmitCommentForm({
  commentableId,
  commentableType,
  onSubmitSuccess,
}: UseSubmitCommentFormProps) {
  const { t } = useTranslation();

  const { targetUserDisplayName } = useCommentListContext();

  const addCommentFormSchema = z.object({
    body: z
      .string()
      .min(3, { message: t('Must be at least {{val, number}} characters.', { val: 3 }) })
      .max(2000, {
        message: t('Must not be longer than {{val, number}} characters.', { val: 2000 }),
      }),
  });
  type FormValues = z.infer<typeof addCommentFormSchema>;

  const draftKey = `comment-${commentableType}-${commentableId}`;

  const draft = loadDraft<FormValues>(draftKey);

  const form = useForm<FormValues>({
    resolver: zodResolver(addCommentFormSchema),
    defaultValues: { body: draft.body ?? '' },
  });

  useEffect(() => {
    const currentDraft = loadDraft<FormValues>(draftKey);
    form.reset({ body: currentDraft.body ?? '' });
  }, [draftKey, form]);

  const { clearDraft } = useFormDraft(draftKey, form);

  const mutation = useSubmitCommentMutation();

  const onSubmit = (formValues: FormValues) => {
    toastMessage.promise(
      mutation.mutateAsync({
        route: buildPostRoute({ commentableId, commentableType, targetUserDisplayName }),
        payload: {
          commentableId,
          commentableType,
          body: formValues.body,
        },
      }),
      {
        loading: t('Submitting...'),
        success: () => {
          clearDraft();
          onSubmitSuccess?.();

          form.reset({ body: '' });

          return t('Submitted!');
        },
        error: t('Something went wrong.'),
      },
    );
  };

  return { form, mutation, onSubmit };
}

function buildPostRoute({
  commentableId,
  commentableType,
  targetUserDisplayName = '',
}: UseSubmitCommentFormProps & { targetUserDisplayName?: string }): string {
  const commentableTypeRouteMap: Record<App.Community.Enums.CommentableType, string> = {
    'achievement.comment': route('api.achievement.comment.store', { achievement: commentableId }),

    'trigger.ticket.comment': 'TODO',

    'forum-topic-comment': 'TODO',

    'game.comment': route('api.game.comment.store', { game: commentableId }),

    'game-hash.comment': route('api.game.hashes.comment.store', { game: commentableId }),

    'game-modification.comment': route('api.game.modification-comment.store', {
      game: commentableId,
    }),

    'leaderboard.comment': route('api.leaderboard.comment.store', { leaderboard: commentableId }),

    'achievement-set-claim.comment': route('api.game.claims.comment.store', {
      game: commentableId,
    }),

    'user.comment': route('api.user.comment.store', { user: targetUserDisplayName }),

    'user-activity.comment': 'TODO',

    'user-moderation.comment': route('api.user.moderation-comment.store', {
      user: targetUserDisplayName,
    }),
  };

  return commentableTypeRouteMap[commentableType];
}
