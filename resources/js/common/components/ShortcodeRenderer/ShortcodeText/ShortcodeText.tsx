import type { FC } from 'react';

interface ShortcodeTextProps {
  content: string;
}

export const ShortcodeText: FC<ShortcodeTextProps> = ({ content }) => {
  const restoredContent = content.replace(/\{(\/?[^{}]+)}/g, '[$1]');

  return <span>{restoredContent}</span>;
};
