import { usePageProps } from './usePageProps';

/**
 * Namespaces a draft key by its author. sessionStorage outlives a logout and
 * login in the same tab, so an unscoped key would hand one user's unsubmitted
 * content to whoever signs in next.
 * Returns null when nobody is signed in, which disables persistence.
 */
export function useUserScopedDraftKey(key: string | null): string | null {
  const { auth } = usePageProps();

  if (!key || !auth) {
    return null;
  }

  return `${key}-${auth.user.id}`;
}
