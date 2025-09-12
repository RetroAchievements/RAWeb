import type { FC, ReactNode } from 'react';
import type { RouteParams } from 'ziggy-js';
import { route } from 'ziggy-js';

import { InertiaLink } from '@/common/components/InertiaLink';
import { usePageProps } from '@/common/hooks/usePageProps';

type GamePageLinkProps = RouteParams<'game.show'> & {
  children: ReactNode;
};

export const GamePageLink: FC<GamePageLinkProps> = ({ children, ...params }) => {
  const { auth } = usePageProps();

  if (auth?.user.enableBetaFeatures) {
    return (
      <InertiaLink href={route('game.show', params as RouteParams<'game.show'>)}>
        {children}
      </InertiaLink>
    );
  }

  return <a href={route('game.show', params as RouteParams<'game.show'>)}>{children}</a>;
};
