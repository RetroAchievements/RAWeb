import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuMessageCircleWarning } from 'react-icons/lu';

import { BaseButton, baseButtonVariants } from '@/common/components/+vendor/BaseButton';
import { toastMessage } from '@/common/components/+vendor/BaseToaster';
import { usePageProps } from '@/common/hooks/usePageProps';

import { useCreateOfficialForumTopicMutation } from '../../hooks/mutations/useCreateOfficialForumTopicMutation';

interface OfficialForumTopicButtonProps {
  game: App.Platform.Data.Game;
}

export const OfficialForumTopicButton: FC<OfficialForumTopicButtonProps> = ({ game }) => {
  const { can } = usePageProps<App.Platform.Data.EventShowPageProps>();

  const { t } = useTranslation();

  const mutation = useCreateOfficialForumTopicMutation();

  const handleCreateClick = async () => {
    if (!confirm(t('Are you sure you want to create the official forum topic for this page?'))) {
      return false;
    }

    await toastMessage.promise(mutation.mutateAsync({ gameId: game.id }), {
      loading: t('Creating...'),
      success: t('Created!'),
      error: t('Something went wrong.'),
    });
  };

  if (!game?.forumTopicId && can.createGameForumTopic) {
    return (
      <BaseButton size="sm" className="flex max-h-[28px] gap-1.5" onClick={handleCreateClick}>
        <LuMessageCircleWarning className="size-4 text-neutral-300" />
        <span>{t('Create New Forum Topic')}</span>
      </BaseButton>
    );
  }

  if (!game?.forumTopicId && !can.createGameForumTopic) {
    return null;
  }

  return (
    <a
      href={`/viewtopic.php?t=${game.forumTopicId}`}
      className={baseButtonVariants({ size: 'sm', className: 'flex max-h-[28px] gap-1.5' })}
    >
      <LuMessageCircleWarning className="size-4 text-neutral-300 light:text-neutral-700" />
      <span>{t('Official Forum Topic')}</span>
    </a>
  );
};
