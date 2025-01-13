import type { FC } from 'react';

interface ShortcodeImgProps {
  src: string;
}

export const ShortcodeImg: FC<ShortcodeImgProps> = ({ src }) => {
  return <img className="inline-image" src={src} />;
};
