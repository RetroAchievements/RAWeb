import { z } from 'zod';

/**
 * Zod 4 shifts some error messages from underneath us, and the new error messages
 * are frankly not very good. We shim the configure here to keep using our stuff
 * from Zod 3.
 */
export function configureZod(): void {
  z.config({
    customError: (issue) =>
      issue.code === 'invalid_type' && issue.input === undefined ? 'Required' : undefined,
  });
}
