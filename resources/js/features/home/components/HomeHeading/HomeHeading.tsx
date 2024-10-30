import type { FC, ReactNode } from 'react';

/**
 * If you need this outside the Home feature, just extract it into @/common/components.
 */

interface HomeHeadingProps {
  children: ReactNode;
}

export const HomeHeading: FC<HomeHeadingProps> = ({ children }) => {
  return <h2 className="border-b-0 text-xl font-semibold">{children}</h2>;
};
