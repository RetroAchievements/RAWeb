import './GlowingImage.css';

import type { ComponentPropsWithoutRef, CSSProperties, FC, ImgHTMLAttributes } from 'react';

import { useCardTooltip } from '@/common/hooks/useCardTooltip';
import { usePageProps } from '@/common/hooks/usePageProps';
import type { BaseAvatarProps } from '@/common/models';

import { GameTitle } from '../GameTitle';

type GameAvatarProps = BaseAvatarProps &
  App.Platform.Data.Game & {
    decoding?: ImgHTMLAttributes<HTMLImageElement>['decoding'];
    loading?: ImgHTMLAttributes<HTMLImageElement>['loading'];
    shouldGlow?: boolean;
    showHoverCardProgressForUsername?: string;
  };

export const GameAvatar: FC<GameAvatarProps> = ({
  id,
  badgeUrl,
  showHoverCardProgressForUsername,
  title,
  decoding = 'async',
  loading = 'lazy',
  shouldGlow = false,
  showImage = true,
  showLabel = true,
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

      {title && showLabel ? <GameTitle title={title} /> : null}
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
