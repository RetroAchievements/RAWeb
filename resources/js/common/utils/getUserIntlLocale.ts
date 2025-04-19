import type { AuthenticatedUser } from '../models';

export function getUserIntlLocale(user: AuthenticatedUser | undefined): string {
  return user?.locale?.replace('_', '-') ?? 'en-us';
}
