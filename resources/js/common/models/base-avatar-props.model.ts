// This is strongly typed so we don't wind up with 100 different possible sizes.
// If possible, use one of these sane defaults. Only add another one if necessary.
type AvatarSize = 8 | 16 | 24 | 32 | 48 | 64 | 128;

export interface BaseAvatarProps {
  hasTooltip?: boolean;
  showImage?: boolean;
  showLabel?: boolean;
  size?: AvatarSize;
}
