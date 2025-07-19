import { useScrolling } from 'react-use';
import type { Mock } from 'vitest';

import { renderHook } from '@/test';

import { useActivePlayerScrollObserver } from './useActivePlayerScrollObserver';

vi.mock('react-use', async (importOriginaol) => {
  const originalModule = await importOriginaol();

  return {
    ...(originalModule as any),
    useScrolling: vi.fn(),
  };
});

describe('Hook: useActivePlayerScrollObserver', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders without crashing', () => {
    // ARRANGE
    (useScrolling as Mock).mockReturnValue(false);

    const { result } = renderHook(() => useActivePlayerScrollObserver());

    // ASSERT
    expect(result.current).toBeDefined();
    expect(result.current.scrollRef).toBeDefined();
    expect(result.current.hasScrolled).toBeDefined();
  });

  it('initializes with hasScrolled as false', () => {
    // ARRANGE
    (useScrolling as Mock).mockReturnValue(false);

    const { result } = renderHook(() => useActivePlayerScrollObserver());

    // ASSERT
    expect(result.current.hasScrolled).toEqual(false);
  });

  it('given scrolling is detected, sets hasScrolled to true', () => {
    // ARRANGE
    (useScrolling as Mock).mockReturnValue(false);

    const { result, rerender } = renderHook(() => useActivePlayerScrollObserver());

    // ACT
    (useScrolling as Mock).mockReturnValue(true);
    rerender();

    // ASSERT
    expect(result.current.hasScrolled).toEqual(true);
  });

  it('maintains hasScrolled as true even after scrolling stops', () => {
    // ARRANGE
    (useScrolling as Mock).mockReturnValue(false);

    const { result, rerender } = renderHook(() => useActivePlayerScrollObserver());

    (useScrolling as Mock).mockReturnValue(true);
    rerender();

    // ACT
    (useScrolling as Mock).mockReturnValue(false);
    rerender();

    // ASSERT
    expect(result.current.hasScrolled).toEqual(true);
  });
});
