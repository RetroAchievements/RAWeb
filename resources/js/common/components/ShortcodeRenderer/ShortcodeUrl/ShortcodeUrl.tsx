import type { FC, ReactNode } from 'react';
import { useTranslation } from 'react-i18next';

import { buildUserContentLink } from '@/common/utils/buildUserContentLink';

interface ShortcodeUrlProps {
  children: ReactNode;
  href: string;
}

export const ShortcodeUrl: FC<ShortcodeUrlProps> = ({ children, href }) => {
  const { t } = useTranslation();

  const parsedLink = buildUserContentLink(href);

  if (!parsedLink) {
    return <span className="line-through">{t('Broken Link')}</span>;
  }

  return (
    <a
      href={parsedLink.href}
      data-testid={`url-embed-${parsedLink.destination}`}
      className="inline-block"
      rel={parsedLink.isExternal ? 'nofollow noopener' : undefined}
    >
      {children}
    </a>
  );
};
