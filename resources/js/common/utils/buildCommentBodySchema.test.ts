import type { TFunction } from 'i18next';

import { buildCommentBodySchema } from './buildCommentBodySchema';

describe('Util: buildCommentBodySchema', () => {
  it('given a body of 3 to 2000 characters, succeeds', () => {
    // ARRANGE
    const t = ((key: string) => key) as TFunction;
    const schema = buildCommentBodySchema(t);

    // ACT
    const shortestResult = schema.safeParse('abc');
    const longestResult = schema.safeParse('a'.repeat(2000));

    // ASSERT
    expect(shortestResult.success).toEqual(true);
    expect(longestResult.success).toEqual(true);
  });

  it('given a body under 3 characters, fails', () => {
    // ARRANGE
    const t = ((key: string) => key) as TFunction;
    const schema = buildCommentBodySchema(t);

    // ACT
    const result = schema.safeParse('ab');

    // ASSERT
    expect(result.success).toEqual(false);
    expect(result.error?.issues[0].message).toEqual('Must be at least {{val, number}} characters.');
  });

  it('given a body over 2000 characters, fails', () => {
    // ARRANGE
    const t = ((key: string) => key) as TFunction;
    const schema = buildCommentBodySchema(t);

    // ACT
    const result = schema.safeParse('a'.repeat(2001));

    // ASSERT
    expect(result.success).toEqual(false);
    expect(result.error?.issues[0].message).toEqual(
      'Must not be longer than {{val, number}} characters.',
    );
  });
});
