import type { FC, ReactNode } from 'react';
import { useTranslation } from 'react-i18next';

interface ShortcodeUrlProps {
  children: ReactNode;
  href: string;
}

export const ShortcodeUrl: FC<ShortcodeUrlProps> = ({ children, href }) => {
  const { t } = useTranslation();

  const normalizedHref = normalizeUrl(href);

  if (!normalizedHref) {
    return <span className="line-through">{t('Broken Link')}</span>;
  }

  // For external URLs, use the redirect route.
  const finalUrl = normalizedHref.includes('retroachievements.org')
    ? normalizedHref
    : route('redirect', { url: normalizedHref });

  return (
    <a
      href={finalUrl}
      data-testid={`url-embed-${normalizedHref}`}
      className="inline-block"
      rel={!normalizedHref.includes('retroachievements.org') ? 'nofollow noopener' : undefined}
    >
      {children}
    </a>
  );
};

function normalizeUrl(href: string): string {
  if (!href) {
    return '';
  }

  try {
    const url = new URL(href.startsWith('http') ? href : `https://${href}`);

    // Force HTTPS for internal links.
    if (url.hostname.endsWith('retroachievements.org') && url.protocol === 'http:') {
      url.protocol = 'https:';
    }

    return url.toString();
  } catch (error) {
    console.debug('Failed to normalize URL:', href, error);

    return '';
  }
}
