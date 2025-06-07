import { act, renderHook } from '@/test';

import { useGlobalSearchHotkey } from './useGlobalSearchHotkey';

describe('Hook: useGlobalSearchHotkey', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('adds a keydown event listener on mount', () => {
    // ARRANGE
    const addEventListenerSpy = vi.spyOn(document, 'addEventListener');
    const mockOnOpenChange = vi.fn();

    // ACT
    renderHook(() => useGlobalSearchHotkey({ onOpenChange: mockOnOpenChange }));

    // ASSERT
    expect(addEventListenerSpy).toHaveBeenCalledWith('keydown', expect.any(Function));
  });

  it('removes the keydown event listener on unmount', () => {
    // ARRANGE
    const removeEventListenerSpy = vi.spyOn(document, 'removeEventListener');
    const mockOnOpenChange = vi.fn();

    // ACT
    const { unmount } = renderHook(() => useGlobalSearchHotkey({ onOpenChange: mockOnOpenChange }));
    unmount();

    // ASSERT
    expect(removeEventListenerSpy).toHaveBeenCalledWith('keydown', expect.any(Function));
  });

  it('given the user presses Cmd+K, calls onOpenChange with true', () => {
    // ARRANGE
    const mockOnOpenChange = vi.fn();
    renderHook(() => useGlobalSearchHotkey({ onOpenChange: mockOnOpenChange }));

    // ACT
    act(() => {
      const event = new KeyboardEvent('keydown', {
        key: 'k',
        metaKey: true,
      });
      document.dispatchEvent(event);
    });

    // ASSERT
    expect(mockOnOpenChange).toHaveBeenCalledWith(true);
  });

  it('given the user presses Ctrl+K, calls onOpenChange with true', () => {
    // ARRANGE
    const mockOnOpenChange = vi.fn();
    renderHook(() => useGlobalSearchHotkey({ onOpenChange: mockOnOpenChange }));

    // ACT
    act(() => {
      const event = new KeyboardEvent('keydown', {
        key: 'k',
        ctrlKey: true,
      });
      document.dispatchEvent(event);
    });

    // ASSERT
    expect(mockOnOpenChange).toHaveBeenCalledWith(true);
  });

  it('given the user presses Cmd+K, prevents the default browser behavior', () => {
    // ARRANGE
    const mockOnOpenChange = vi.fn();
    renderHook(() => useGlobalSearchHotkey({ onOpenChange: mockOnOpenChange }));

    // ACT
    const event = new KeyboardEvent('keydown', {
      key: 'k',
      metaKey: true,
    });
    const preventDefaultSpy = vi.spyOn(event, 'preventDefault');

    act(() => {
      document.dispatchEvent(event);
    });

    // ASSERT
    expect(preventDefaultSpy).toHaveBeenCalled();
  });

  it('given the user presses only K without modifier, does not call onOpenChange', () => {
    // ARRANGE
    const mockOnOpenChange = vi.fn();
    renderHook(() => useGlobalSearchHotkey({ onOpenChange: mockOnOpenChange }));

    // ACT
    act(() => {
      const event = new KeyboardEvent('keydown', {
        key: 'k',
      });
      document.dispatchEvent(event);
    });

    // ASSERT
    expect(mockOnOpenChange).not.toHaveBeenCalled();
  });

  it('given the user presses Cmd+J, does not call onOpenChange', () => {
    // ARRANGE
    const mockOnOpenChange = vi.fn();
    renderHook(() => useGlobalSearchHotkey({ onOpenChange: mockOnOpenChange }));

    // ACT
    act(() => {
      const event = new KeyboardEvent('keydown', {
        key: 'j',
        metaKey: true,
      });
      document.dispatchEvent(event);
    });

    // ASSERT
    expect(mockOnOpenChange).not.toHaveBeenCalled();
  });

  it('given both Cmd and Ctrl are pressed with K, still calls onOpenChange', () => {
    // ARRANGE
    const mockOnOpenChange = vi.fn();
    renderHook(() => useGlobalSearchHotkey({ onOpenChange: mockOnOpenChange }));

    // ACT
    act(() => {
      const event = new KeyboardEvent('keydown', {
        key: 'k',
        metaKey: true,
        ctrlKey: true,
      });
      document.dispatchEvent(event);
    });

    // ASSERT
    expect(mockOnOpenChange).toHaveBeenCalledWith(true);
    expect(mockOnOpenChange).toHaveBeenCalledTimes(1);
  });
});
