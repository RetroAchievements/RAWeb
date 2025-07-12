import type { FC } from 'react';

interface ShortcodeImgProps {
  src: string;
}

export const ShortcodeImg: FC<ShortcodeImgProps> = ({ src }) => {
  // eslint-disable-next-line jsx-a11y/alt-text -- the shortcode doesn't support alt attributes
  return <img className="inline-image" src={src} />;
};
