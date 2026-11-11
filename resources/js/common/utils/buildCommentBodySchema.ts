import type { TFunction } from 'i18next';
import { z } from 'zod';

export function buildCommentBodySchema(t: TFunction) {
  return z
    .string()
    .min(3, { message: t('Must be at least {{val, number}} characters.', { val: 3 }) })
    .max(2000, {
      message: t('Must not be longer than {{val, number}} characters.', { val: 2000 }),
    });
}
