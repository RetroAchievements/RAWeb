import { createAuthenticatedUser } from '@/common/models';
import { renderHook } from '@/test';

import { useUserScopedDraftKey } from './useUserScopedDraftKey';

describe('Hook: useUserScopedDraftKey', () => {
  it('given a signed-in user, namespaces the key with their id', () => {
    // ARRANGE
    const { result } = renderHook(() => useUserScopedDraftKey('create-topic-3'), {
      pageProps: { auth: { user: createAuthenticatedUser({ id: 7 }) } },
    });

    // ASSERT
    expect(result.current).toEqual('create-topic-3-7');
  });

  it('given nobody is signed in, disables persistence', () => {
    // ARRANGE
    const { result } = renderHook(() => useUserScopedDraftKey('create-topic-3'), {
      pageProps: { auth: null },
    });

    // ASSERT
    expect(result.current).toBeNull();
  });

  it('given a null key, stays null', () => {
    // ARRANGE
    const { result } = renderHook(() => useUserScopedDraftKey(null), {
      pageProps: { auth: { user: createAuthenticatedUser({ id: 7 }) } },
    });

    // ASSERT
    expect(result.current).toBeNull();
  });
});
