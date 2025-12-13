import type { FC, ReactNode } from 'react';

import { InertiaLink } from '@/common/components/InertiaLink';

interface ResultItemProps {
  children: ReactNode;
  href: string;
  isInertiaLink: boolean;
}

export const ResultItem: FC<ResultItemProps> = ({ children, href, isInertiaLink }) => {
  const Link = isInertiaLink ? InertiaLink : 'a';

  return (
    <Link
      href={href}
      className="rounded-lg p-3 transition-colors hover:bg-embed light:hover:bg-neutral-100"
      prefetch={isInertiaLink ? 'desktop-hover-only' : undefined}
    >
      {children}
    </Link>
  );
};
