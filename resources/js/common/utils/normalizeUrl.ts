export function normalizeUrl(href: string): string {
  const trimmedHref = href.trim();

  if (!trimmedHref) {
    return '';
  }

  const explicitScheme = trimmedHref.match(/^[a-zA-Z][a-zA-Z0-9+.-]*:/)?.[0].toLowerCase();
  if (explicitScheme && !['http:', 'https:'].includes(explicitScheme)) {
    return '';
  }

  try {
    const url = new URL(explicitScheme ? trimmedHref : `https://${trimmedHref}`);

    if (!['http:', 'https:'].includes(url.protocol)) {
      return '';
    }

    if (url.hostname.endsWith('retroachievements.org') && url.protocol === 'http:') {
      url.protocol = 'https:';
    }

    return url.toString();
  } catch (error) {
    console.debug('Failed to normalize URL:', href, error);

    return '';
  }
}
