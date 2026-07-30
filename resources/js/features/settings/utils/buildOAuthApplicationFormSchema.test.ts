import type { TFunction } from 'i18next';

import { buildOAuthApplicationFormSchema } from './buildOAuthApplicationFormSchema';

describe('Util: buildOAuthApplicationFormSchema', () => {
  const t = ((key: string) => key) as TFunction;

  it('is defined', () => {
    // ASSERT
    expect(buildOAuthApplicationFormSchema).toBeDefined();
  });

  it('accepts a valid application, but rejects an unsafe redirect URI', () => {
    // ARRANGE
    const schema = buildOAuthApplicationFormSchema(t);

    // ACT
    const validResult = schema.safeParse({
      name: 'My Integration',
      redirectUri: 'https://example.com/callback',
    });
    const invalidResult = schema.safeParse({
      name: 'My Integration',
      redirectUri: 'https://example.com/callback#fragment',
    });

    // ASSERT
    expect(validResult.success).toEqual(true);
    expect(invalidResult.success).toEqual(false);
  });

  it('enforces the name length bounds', () => {
    // ARRANGE
    const schema = buildOAuthApplicationFormSchema(t);

    // ACT
    const tooShortResult = schema.safeParse({
      name: 'ab',
      redirectUri: 'https://example.com/callback',
    });
    const longEnoughResult = schema.safeParse({
      name: 'abc',
      redirectUri: 'https://example.com/callback',
    });

    // ASSERT
    expect(tooShortResult.success).toEqual(false);
    expect(longEnoughResult.success).toEqual(true);
  });
});
