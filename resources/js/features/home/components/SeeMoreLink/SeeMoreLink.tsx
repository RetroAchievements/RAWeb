import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { InertiaLink } from '@/common/components/InertiaLink';
import type { LinkPrefetchBehavior } from '@/common/models';

interface SeeMoreLinkProps {
  href: string;

  /**
   * If the destination page is also a React page, we should client-side
   * route to improve the performance of loading that page.
   */
  asClientSideRoute?: boolean;

  /**
   * Optional. You can manually adjust the prefetch strategy of this link.
   * Defaults to "never".
   */
  prefetch?: LinkPrefetchBehavior;
}

export const SeeMoreLink: FC<SeeMoreLinkProps> = ({ href, asClientSideRoute }) => {
  const { t } = useTranslation();

  const Wrapper = asClientSideRoute ? InertiaLink : 'a';

  return (
    <div className="mt-1.5 flex w-full justify-end text-xs">
      <Wrapper href={href}>{t('See more')}</Wrapper>
    </div>
  );
};
