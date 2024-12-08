// This is strongly typed so we don't wind up with 100 different possible sizes.
// If possible, use one of these sane defaults. Only add another one if necessary.
export type AvatarSize = 8 | 16 | 24 | 32 | 40 | 48 | 64 | 96 | 128;

export interface BaseAvatarProps {
  hasTooltip?: boolean;
  shouldLink?: boolean;
  showImage?: boolean;
  showLabel?: boolean;
  size?: AvatarSize;
}
