import { route } from 'ziggy-js';

import { normalizeUrl } from './normalizeUrl';

interface NormalizedUserLink {
  /** Post-normalization URL */
  destination: string;
  href: string;
  isExternal: boolean;
}

export function buildUserContentLink(rawUrl: string): NormalizedUserLink | null {
  const destination = normalizeUrl(rawUrl);
  if (!destination) {
    return null;
  }

  // Inspect the host name instead of scanning the whole URL, so a link that only mentions the
  // domain somewhere inside its path is still treated as external. This prevents someone from
  // bypassing the redirect route.
  const { hostname: host } = new URL(destination);
  const isExternal = host !== 'retroachievements.org' && !host.endsWith('.retroachievements.org');

  return {
    destination,
    isExternal,
    href: isExternal ? route('redirect', { url: destination }) : destination,
  };
}
