import { useLaravelReactI18n } from 'laravel-react-i18n';
import type { FC } from 'react';
import { LuX } from 'react-icons/lu';

import { BaseButton } from '../+vendor/BaseButton';
import { toastMessage } from '../+vendor/BaseToaster';
import { BaseTooltip, BaseTooltipContent, BaseTooltipTrigger } from '../+vendor/BaseTooltip';
import { useDeleteCommentMutation } from './useDeleteCommentMutation';

type DeleteCommentButtonProps = App.Community.Data.Comment & { onDeleteSuccess?: () => void };

export const DeleteCommentButton: FC<DeleteCommentButtonProps> = ({
  onDeleteSuccess,
  ...comment
}) => {
  const { t } = useLaravelReactI18n();

  const mutation = useDeleteCommentMutation();

  const handleClick = () => {
    if (!confirm(t('Are you sure you want to permanently delete this comment?'))) {
      return;
    }

    toastMessage.promise(mutation.mutateAsync(comment), {
      loading: t('Deleting...'),
      success: () => {
        onDeleteSuccess?.();

        return t('Deleted!');
      },
      error: t('Something went wrong.'),
    });
  };

  return (
    <BaseTooltip>
      <BaseTooltipTrigger asChild>
        <BaseButton
          aria-label="Delete comment"
          variant="destructive"
          size="icon"
          className="!h-5 !w-5"
          onClick={handleClick}
        >
          <LuX className="h-4 w-4" />
        </BaseButton>
      </BaseTooltipTrigger>

      <BaseTooltipContent>
        <p className="text-xs">{t('Delete comment')}</p>
      </BaseTooltipContent>
    </BaseTooltip>
  );
};
