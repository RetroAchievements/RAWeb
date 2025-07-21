import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuMessageSquare } from 'react-icons/lu';
import { route } from 'ziggy-js';

import { baseButtonVariants } from '@/common/components/+vendor/BaseButton';
import { InertiaLink } from '@/common/components/InertiaLink';

interface PlayableOfficialForumTopicButtonProps {
  game: App.Platform.Data.Game;

  backingGame?: App.Platform.Data.Game;
}

export const PlayableOfficialForumTopicButton: FC<PlayableOfficialForumTopicButtonProps> = ({
  backingGame,
  game,
}) => {
  const { t } = useTranslation();

  if (!game?.forumTopicId) {
    return null;
  }

  const shouldShowGameAndSetTopicLinks = backingGame && backingGame.id !== game.id;

  return (
    <div className="flex flex-col gap-1">
      <InertiaLink
        href={route('forum-topic.show', { topic: game.forumTopicId as number })}
        className={baseButtonVariants({
          className: 'flex w-full items-center !justify-start gap-2 border-l-4 border-l-link',
        })}
        prefetch="desktop-hover-only"
      >
        <LuMessageSquare className="size-4 brightness-125" />
        <span>
          {shouldShowGameAndSetTopicLinks ? t('Game Forum Topic') : t('Official Forum Topic')}
        </span>
      </InertiaLink>

      {shouldShowGameAndSetTopicLinks ? (
        <InertiaLink
          href={route('forum-topic.show', { topic: backingGame.forumTopicId as number })}
          className={baseButtonVariants({
            className: 'flex w-full items-center !justify-start gap-2 border-l-4 border-l-link',
          })}
          prefetch="desktop-hover-only"
        >
          <LuMessageSquare className="size-4 brightness-125" />
          {t('Set Forum Topic')}
        </InertiaLink>
      ) : null}
    </div>
  );
};
