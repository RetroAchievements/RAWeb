import { zodResolver } from '@hookform/resolvers/zod';
import { useMutation } from '@tanstack/react-query';
import axios from 'axios';
import { useForm } from 'react-hook-form';
import { useTranslation } from 'react-i18next';
import { z } from 'zod';

import { toastMessage } from '@/common/components/+vendor/BaseToaster';
import { ArticleType } from '@/common/utils/generatedAppConstants';

import { useCommentListContext } from './CommentListContext';

interface UseSubmitCommentFormProps {
  commentableId: number | string;
  commentableType: keyof typeof ArticleType;

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
      .min(3, { message: t('Comment must be at least 3 characters.') })
      .max(2000, { message: t('Comment must not be longer than 2,000 characters.') }),
  });

  type FormValues = z.infer<typeof addCommentFormSchema>;

  const form = useForm<FormValues>({
    resolver: zodResolver(addCommentFormSchema),
    defaultValues: { body: '' },
  });

  const mutation = useMutation({
    mutationFn: (formValues: FormValues) => {
      return axios.post(buildPostRoute({ commentableId, commentableType, targetUserDisplayName }), {
        commentableId,
        commentableType: ArticleType[commentableType],
        body: formValues.body,
      });
    },
  });

  const onSubmit = (formValues: FormValues) => {
    toastMessage.promise(mutation.mutateAsync(formValues), {
      loading: t('Submitting...'),
      success: () => {
        onSubmitSuccess?.();

        return t('Submitted!');
      },
      error: t('Something went wrong.'),
    });
  };

  return { form, mutation, onSubmit };
}

function buildPostRoute({
  commentableId,
  commentableType,
  targetUserDisplayName = '',
}: UseSubmitCommentFormProps & { targetUserDisplayName?: string }): string {
  const commentableTypeRouteMap: Record<keyof typeof ArticleType, string> = {
    Achievement: route('api.achievement.comment.store', { achievement: commentableId }),

    AchievementTicket: 'TODO',

    Forum: 'TODO',

    Game: route('api.game.comment.store', { game: commentableId }),

    GameHash: route('api.game.hashes.comment.store', { game: commentableId }),

    GameModification: 'TODO',

    Leaderboard: route('api.leaderboard.comment.store', { leaderboard: commentableId }),

    News: 'TODO',

    SetClaim: 'TODO',

    User: route('api.user.comment.store', { user: targetUserDisplayName }),

    UserModeration: 'TODO',
  };

  return commentableTypeRouteMap[commentableType];
}
