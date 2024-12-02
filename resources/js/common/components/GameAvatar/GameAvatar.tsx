import './GlowingImage.css';

import type { ComponentPropsWithoutRef, CSSProperties, FC, ImgHTMLAttributes } from 'react';

import { useCardTooltip } from '@/common/hooks/useCardTooltip';
import { usePageProps } from '@/common/hooks/usePageProps';
import type { BaseAvatarProps } from '@/common/models';

import { GameTitle } from '../GameTitle';
import { SystemChip } from '../SystemChip';

type GameAvatarProps = BaseAvatarProps &
  App.Platform.Data.Game & {
    decoding?: ImgHTMLAttributes<HTMLImageElement>['decoding'];
    gameTitleClassName?: string;
    loading?: ImgHTMLAttributes<HTMLImageElement>['loading'];
    shouldGlow?: boolean;
    showHoverCardProgressForUsername?: string;
    showSystemChip?: boolean;
  };

export const GameAvatar: FC<GameAvatarProps> = ({
  badgeUrl,
  gameTitleClassName,
  id,
  showHoverCardProgressForUsername,
  system,
  title,
  decoding = 'async',
  loading = 'lazy',
  shouldGlow = false,
  showImage = true,
  showLabel = true,
  showSystemChip = false,
  size = 32,
  hasTooltip = true,
}) => {
  const { auth } = usePageProps();

  const { cardTooltipProps } = useCardTooltip({
    dynamicType: 'game',
    dynamicId: id,
    dynamicContext: showHoverCardProgressForUsername ?? auth?.user.displayName,
  });

  return (
    <a
      href={route('game.show', { game: id })}
      className="flex items-center gap-2"
      {...(hasTooltip ? cardTooltipProps : undefined)}
    >
      {showImage ? (
        <>
          {shouldGlow ? (
            <GlowingImage width={size} height={size} src={badgeUrl} alt={title ?? 'Game'} />
          ) : (
            <img
              loading={loading}
              decoding={decoding}
              width={size}
              height={size}
              src={badgeUrl}
              alt={title ?? 'Game'}
              className="rounded-sm"
            />
          )}
        </>
      ) : null}

      <div className="flex flex-col gap-0.5">
        {title && showLabel ? <GameTitle title={title} className={gameTitleClassName} /> : null}

        {system && showSystemChip ? (
          <SystemChip {...system} className="text-text hover:text-text" />
        ) : null}
      </div>
    </a>
  );
};

type GlowingImageProps = Pick<ComponentPropsWithoutRef<'img'>, 'src' | 'alt' | 'width' | 'height'>;

const GlowingImage: FC<GlowingImageProps> = ({ src, ...rest }) => {
  return (
    <div className="glowing-image-root" style={{ '--img-url': `url(${src})` } as CSSProperties}>
      <img src={src} className="glowing-image" loading="eager" decoding="sync" {...rest} />
    </div>
  );
};
