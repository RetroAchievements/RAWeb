import type { MockInstance } from 'vitest';

import { renderHook } from '@/test';
import { createZiggyProps } from '@/test/factories';

import { useAutoScrollToMessage } from './useAutoScrollToMessage';

describe('Hook: useAutoScrollToMessage', () => {
  let mockScrollIntoView: ReturnType<typeof vi.fn>;
  let mockGetElementById: MockInstance<typeof document.getElementById>;

  beforeEach(() => {
    vi.clearAllMocks();
    vi.clearAllTimers();
    vi.useFakeTimers();

    mockScrollIntoView = vi.fn();
    mockGetElementById = vi.spyOn(document, 'getElementById');
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { result } = renderHook(() => useAutoScrollToMessage(), {
      pageProps: { ziggy: createZiggyProps() },
    });

    // ASSERT
    expect(result).toBeTruthy();
  });

  it('given there is no message query parameter, does not attempt to scroll', () => {
    // ARRANGE
    renderHook(() => useAutoScrollToMessage(), {
      pageProps: { ziggy: createZiggyProps({ query: {} }) },
    });
    vi.runAllTimers();

    // ASSERT
    expect(mockGetElementById).not.toHaveBeenCalled();
  });

  it('given there is a message query parameter and the element exists, scrolls to the element', () => {
    // ARRANGE
    const mockElement = {
      scrollIntoView: mockScrollIntoView,
    };
    mockGetElementById.mockReturnValue(mockElement as any);

    // ACT
    renderHook(() => useAutoScrollToMessage(), {
      pageProps: { ziggy: createZiggyProps({ query: { message: '12345' } }) },
    });
    vi.runAllTimers();

    // ASSERT
    expect(mockGetElementById).toHaveBeenCalledWith('12345');
    expect(mockScrollIntoView).toHaveBeenCalledWith({
      behavior: 'smooth',
      block: 'start',
    });
  });

  it('given there is a message query parameter but the element does not exist, does not throw an error', () => {
    // ARRANGE
    mockGetElementById.mockReturnValue(null);

    // ASSERT
    expect(() => {
      renderHook(() => useAutoScrollToMessage(), {
        pageProps: { ziggy: createZiggyProps({ query: { message: '99999' } }) },
      });
      vi.runAllTimers();
    }).not.toThrow();

    expect(mockGetElementById).toHaveBeenCalledWith('99999');
    expect(mockScrollIntoView).not.toHaveBeenCalled();
  });
});
