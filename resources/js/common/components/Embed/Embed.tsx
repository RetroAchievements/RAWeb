import type { FC, HTMLAttributes, ReactNode } from 'react';

import { cn } from '@/common/utils/cn';

interface EmbedProps extends HTMLAttributes<HTMLDivElement> {
  children: ReactNode;

  className?: string;
}

export const Embed: FC<EmbedProps> = ({ className, ...rest }) => {
  return (
    <div className={cn('-mx-3 rounded bg-embed px-3 py-4 sm:mx-0 sm:px-4', className)} {...rest} />
  );
};
