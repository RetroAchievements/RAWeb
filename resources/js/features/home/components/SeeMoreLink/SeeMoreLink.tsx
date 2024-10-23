import { Link } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';
import type { FC } from 'react';

interface SeeMoreLinkProps {
  href: string;

  /**
   * If the destination page is also a React page, we should client-side
   * route to improve the performance of loading that page.
   */
  asClientSideRoute?: boolean;
}

export const SeeMoreLink: FC<SeeMoreLinkProps> = ({ href, asClientSideRoute }) => {
  const { t } = useLaravelReactI18n();

  const Wrapper = asClientSideRoute ? Link : 'a';

  return (
    <div className="mt-1.5 flex w-full justify-end text-xs">
      <Wrapper href={href}>{t('See more')}</Wrapper>
    </div>
  );
};
