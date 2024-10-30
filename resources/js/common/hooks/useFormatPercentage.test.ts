import { renderHook } from '@/test';

import { createAuthenticatedUser } from '../models';
import { useFormatPercentage } from './useFormatPercentage';

describe('Hook: useFormatPercentage', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { result } = renderHook(() => useFormatPercentage(), {
      pageProps: { auth: { user: createAuthenticatedUser({ locale: undefined }) } },
    });

    // ASSERT
    expect(result).toBeDefined();
  });

  it('returns a function that can be used to format localized percentages', () => {
    // ARRANGE
    const { result } = renderHook(() => useFormatPercentage(), {
      pageProps: { auth: { user: createAuthenticatedUser({ locale: undefined }) } },
    });

    // ACT
    const formatted = (result.current as ReturnType<typeof useFormatPercentage>).formatPercentage(
      0.1234,
    );

    // ASSERT
    expect(formatted).toEqual('12.34%');
  });
});
