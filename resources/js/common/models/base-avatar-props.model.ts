/**
 * This is strongly-typed so we don't wind up with 100 different possible sizes.
 * If possible, use one of these sane defaults.
 * When adding another one, try to ensure it corresponds to a Tailwind size-* class.
 */
export type AvatarSize = 8 | 16 | 24 | 28 | 32 | 40 | 48 | 64 | 96 | 128;

export interface BaseAvatarProps {
  hasTooltip?: boolean;
  imgClassName?: string;
  shouldLink?: boolean;
  showImage?: boolean;
  showLabel?: boolean;
  size?: AvatarSize;
}
