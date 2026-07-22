/**
 * Mirrors the redirect URI rule the server enforces. A redirect URI may not carry a
 * wildcard or a fragment, and may only be plaintext HTTP when it points at the
 * developer's own machine. Custom schemes are left to the server to adjudicate,
 * because whether they are permitted depends on the client type.
 */
export function isSafeRedirectUri(redirectUri: string): boolean {
  if (redirectUri.includes('*') || redirectUri.includes('#')) {
    return false;
  }

  let url: URL;
  try {
    url = new URL(redirectUri);
  } catch {
    return false;
  }

  if (url.protocol === 'https:') {
    return url.hostname !== '';
  }

  if (url.protocol === 'http:') {
    return ['localhost', '127.0.0.1', '[::1]'].includes(url.hostname);
  }

  return true;
}
