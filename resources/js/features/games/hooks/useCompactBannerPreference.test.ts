import { act, renderHook } from '@/test';

import { useCompactBannerPreference } from './useCompactBannerPreference';

describe('Hook: useCompactBannerPreference', () => {
  it('returns false when the page prop is false', () => {
    // ARRANGE
    const { result } = renderHook(() => useCompactBannerPreference(), {
      pageProps: { prefersCompactBanners: false },
    });

    // ASSERT
    expect(result.current.prefersCompactBanners).toEqual(false);
  });

  it('returns true when the page prop is true', () => {
    // ARRANGE
    const { result } = renderHook(() => useCompactBannerPreference(), {
      pageProps: { prefersCompactBanners: true },
    });

    // ASSERT
    expect(result.current.prefersCompactBanners).toEqual(true);
  });

  it('toggles from false to true', () => {
    // ARRANGE
    const { result } = renderHook(() => useCompactBannerPreference(), {
      pageProps: { prefersCompactBanners: false },
    });

    // ACT
    act(() => {
      result.current.toggleCompactBanners();
    });

    // ASSERT
    expect(result.current.prefersCompactBanners).toEqual(true);
  });

  it('toggles from true to false', () => {
    // ARRANGE
    const { result } = renderHook(() => useCompactBannerPreference(), {
      pageProps: { prefersCompactBanners: true },
    });

    // ACT
    act(() => {
      result.current.toggleCompactBanners();
    });

    // ASSERT
    expect(result.current.prefersCompactBanners).toEqual(false);
  });
});
