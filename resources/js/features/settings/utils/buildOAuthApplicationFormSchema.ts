import type { TFunction } from 'i18next';
import { z } from 'zod';

import { isSafeRedirectUri } from './isSafeRedirectUri';

/**
 * Shared by the registration and management forms so a redirect URI that would be
 * rejected by the server is caught before the round trip.
 */
export function buildOAuthApplicationFormSchema(t: TFunction) {
  return z.object({
    name: z
      .string()
      .min(3, {
        message: t('Must be at least {{val, number}} characters.', { val: 3 }),
      })
      .max(80, {
        message: t('Must not be longer than {{val, number}} characters.', {
          val: 80,
        }),
      }),

    redirectUri: z
      .string()
      .min(1, { message: t('Required') })
      .max(2048, {
        message: t('Must not be longer than {{val, number}} characters.', {
          val: 2048,
        }),
      })
      .refine(isSafeRedirectUri, {
        message: t('Enter a secure redirect URI without wildcards or fragments.'),
      }),
  });
}
