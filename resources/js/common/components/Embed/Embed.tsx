import type { FC, HTMLAttributes, ReactNode } from 'react';

interface EmbedProps extends HTMLAttributes<HTMLDivElement> {
  children: ReactNode;

  className?: string;
}

export const Embed: FC<EmbedProps> = ({ children, className, ...rest }) => {
  return (
    <div className={`-mx-3 rounded bg-embed px-3 py-4 sm:mx-0 sm:px-4 ${className}`} {...rest}>
      {children}
    </div>
  );
};
