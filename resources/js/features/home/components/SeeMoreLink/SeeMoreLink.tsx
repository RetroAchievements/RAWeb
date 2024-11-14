import { Link } from '@inertiajs/react';
import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

interface SeeMoreLinkProps {
  href: string;

  /**
   * If the destination page is also a React page, we should client-side
   * route to improve the performance of loading that page.
   */
  asClientSideRoute?: boolean;
}

export const SeeMoreLink: FC<SeeMoreLinkProps> = ({ href, asClientSideRoute }) => {
  const { t } = useTranslation();

  const Wrapper = asClientSideRoute ? Link : 'a';

  return (
    <div className="mt-1.5 flex w-full justify-end text-xs">
      <Wrapper href={href}>{t('See more')}</Wrapper>
    </div>
  );
};
