import { z } from 'zod';

import { StringifiedUserPreference } from '@/common/utils/generatedAppConstants';

// Auto-generate the form schema from the StringifiedUserPreference enum.
// It's really tedious to manually update this any time we add a preference.

const schemaFields: Record<string, z.ZodBoolean> = {};
for (const key of Object.keys(StringifiedUserPreference)) {
  const value = StringifiedUserPreference[key as keyof typeof StringifiedUserPreference];
  schemaFields[value] = z.boolean();
}

export const websitePrefsFormSchema = z.object(schemaFields);
