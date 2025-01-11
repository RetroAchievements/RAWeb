import type { FC } from 'react';
import { Trans } from 'react-i18next';
import { LuWrench } from 'react-icons/lu';

import { BaseChip } from '@/common/components/+vendor/BaseChip';
import { GameAvatar } from '@/common/components/GameAvatar';
import { useCardTooltip } from '@/common/hooks/useCardTooltip';

interface SharedAuthorReasonProps {
  relatedAuthor: App.Data.User;
  relatedGame: App.Platform.Data.Game;
}

export const SharedAuthorReason: FC<SharedAuthorReasonProps> = ({ relatedAuthor, relatedGame }) => {
  const { cardTooltipProps } = useCardTooltip({
    dynamicType: 'user',
    dynamicId: relatedAuthor.displayName,
  });

  return (
    <BaseChip
      data-testid="shared-author-reason"
      className="flex gap-1.5 py-1 text-neutral-300 xl:whitespace-nowrap"
    >
      <LuWrench className="size-[18px]" />

      <span className="xl:hidden">
        <Trans
          i18nKey="Same <1>dev</1> as"
          components={{
            1: (
              <a
                href={route('user.show', { user: relatedAuthor.displayName })}
                {...cardTooltipProps}
              />
            ),
          }}
        />
      </span>

      <span className="hidden xl:inline">
        <Trans
          i18nKey="By <1>same developer</1> as"
          components={{
            1: (
              <a
                href={route('user.show', { user: relatedAuthor.displayName })}
                {...cardTooltipProps}
              />
            ),
          }}
        />
      </span>

      <GameAvatar {...relatedGame} showLabel={false} size={24} wrapperClassName="inline-block" />
    </BaseChip>
  );
};
