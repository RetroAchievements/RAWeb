import { renderHook, waitFor } from '@/test';

import { useCardTooltip } from './useCardTooltip';

describe('Hook: useCardTooltip', () => {
  beforeAll(() => {
    (window as any).Alpine = {
      initTree: vi.fn(),
      destroyTree: vi.fn(),
    };
  });

  afterEach(() => {
    vi.clearAllMocks();
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { result } = renderHook(() => useCardTooltip({ dynamicId: 1, dynamicType: 'game' }));

    // ASSERT
    expect(result.current.cardTooltipProps).toBeTruthy();
  });

  it('given args, returns the correct cardTooltipProps', () => {
    // ARRANGE
    const { result } = renderHook(() =>
      useCardTooltip({ dynamicId: 1, dynamicType: 'game', dynamicContext: 'Scott' }),
    );

    // ASSERT
    expect(result.current.cardTooltipProps).toEqual({
      'x-data': `tooltipComponent($el, {dynamicType: 'game', dynamicId: '1', dynamicContext: 'Scott'})`,
      'x-on:mouseover': 'showTooltip($event)',
      'x-on:mouseleave': 'hideTooltip',
      'x-on:mousemove': 'trackMouseMovement($event)',
      ref: expect.any(Function),
    });
  });

  it('given dynamicId changes, then Alpine is reinitialized with the new element', async () => {
    // ARRANGE
    const mockElement = document.createElement('div');
    mockElement.setAttribute(
      'x-data',
      "tooltipComponent($el, {dynamicType: 'user', dynamicId: '123', dynamicContext: 'profile'})",
    );

    const initialArgs = { dynamicId: 1, dynamicType: 'game' as const };

    const { result, rerender } = renderHook(({ args }) => useCardTooltip(args), {
      initialProps: { args: initialArgs },
    });

    // ... simulate the ref callback being called with the mock element ...
    result.current.cardTooltipProps.ref(mockElement as any);

    // ACT
    rerender({ args: { dynamicId: 2, dynamicType: 'game' as const } });

    // ASSERT
    await waitFor(() => {
      expect(window.Alpine.destroyTree).toHaveBeenCalledWith(mockElement);
    });

    await waitFor(() => {
      expect(window.Alpine.initTree).toHaveBeenCalledWith(mockElement);
    });
  });
});
