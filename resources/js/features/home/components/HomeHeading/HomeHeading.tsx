import type { FC, ReactNode } from 'react';

import { cn } from '@/common/utils/cn';

/**
 * If you need this outside the Home feature, just extract it into @/common/components.
 */

interface HomeHeadingProps {
  children: ReactNode;

  className?: string;
}

export const HomeHeading: FC<HomeHeadingProps> = ({ children, className }) => {
  return <h2 className={cn('border-b-0 text-xl font-semibold', className)}>{children}</h2>;
};
