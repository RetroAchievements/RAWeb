import type { ReactNode } from 'react';

import { act, renderHook } from '@/test';

import { useGlobalSearchHotkey } from './useGlobalSearchHotkey';

interface HotkeyCase {
  description: string;
  eventInit: KeyboardEventInit;
  expectedOpenChange: boolean;
}

const hotkeyCases: HotkeyCase[] = [
  {
    description: 'given the user presses Cmd+K, calls onOpenChange with true',
    eventInit: { code: 'KeyK', key: 'k', metaKey: true },
    expectedOpenChange: true,
  },
  {
    description: 'given the user presses Ctrl+K, calls onOpenChange with true',
    eventInit: { code: 'KeyK', key: 'k', ctrlKey: true },
    expectedOpenChange: true,
  },
  {
    description: 'given the user presses Cmd+K with uppercase K (e.g. Caps Lock), calls onOpenChange with true',
    eventInit: { code: 'KeyK', key: 'K', metaKey: true },
    expectedOpenChange: true,
  },
  {
    description: 'given the user presses only K without modifier, does not call onOpenChange',
    eventInit: { code: 'KeyK', key: 'k' },
    expectedOpenChange: false,
  },
  {
    description: 'given the user presses Cmd+J, does not call onOpenChange',
    eventInit: { code: 'KeyJ', key: 'j', metaKey: true },
    expectedOpenChange: false,
  },
  {
    description:
      'given a non-QWERTY Latin layout where physical KeyK produces a different ASCII letter (e.g. Dvorak Cmd+T), does not call onOpenChange',
    eventInit: { code: 'KeyK', key: 't', metaKey: true },
    expectedOpenChange: false,
  },
  {
    description:
      'given a non-QWERTY Latin layout where K is at a different physical key (e.g. Dvorak Cmd+K at KeyV), calls onOpenChange with true',
    eventInit: { code: 'KeyV', key: 'k', metaKey: true },
    expectedOpenChange: true,
  },
  {
    description:
      'given a non-Latin layout (e.g. Cyrillic) where physical KeyK produces a non-ASCII character, falls back to e.code and calls onOpenChange with true',
    eventInit: { code: 'KeyK', key: 'л', metaKey: true },
    expectedOpenChange: true,
  },
  {
    description:
      'given a non-Latin layout where a different physical key is pressed, does not call onOpenChange',
    eventInit: { code: 'KeyJ', key: 'о', metaKey: true },
    expectedOpenChange: false,
  },
  {
    description: 'given Cmd+Alt+K, does not call onOpenChange',
    eventInit: { code: 'KeyK', key: 'k', metaKey: true, altKey: true },
    expectedOpenChange: false,
  },
  {
    description: 'given Cmd+Shift+K, does not call onOpenChange',
    eventInit: { code: 'KeyK', key: 'K', metaKey: true, shiftKey: true },
    expectedOpenChange: false,
  },
  {
    description: 'given both Cmd and Ctrl are pressed with K, calls onOpenChange once with true',
    eventInit: { code: 'KeyK', key: 'k', metaKey: true, ctrlKey: true },
    expectedOpenChange: true,
  },
];

describe('Hook: useGlobalSearchHotkey', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('adds a keydown event listener on mount', () => {
    // ARRANGE
    const addEventListenerSpy = vi.spyOn(document, 'addEventListener');
    const mockOnOpenChange = vi.fn();

    // ACT
    renderHook(() => useGlobalSearchHotkey({ onOpenChange: mockOnOpenChange }), {
      wrapper: ({ children }: { children: ReactNode }) => children,
    });

    // ASSERT
    expect(addEventListenerSpy).toHaveBeenCalledWith('keydown', expect.any(Function));
  });

  it('removes the keydown event listener on unmount', () => {
    // ARRANGE
    const removeEventListenerSpy = vi.spyOn(document, 'removeEventListener');
    const mockOnOpenChange = vi.fn();

    // ACT
    const { unmount } = renderHook(
      () => useGlobalSearchHotkey({ onOpenChange: mockOnOpenChange }),
      {
        wrapper: ({ children }: { children: ReactNode }) => children,
      },
    );
    unmount();

    // ASSERT
    expect(removeEventListenerSpy).toHaveBeenCalledWith('keydown', expect.any(Function));
  });

  it('given the user presses Cmd+K, prevents the default browser behavior', () => {
    // ARRANGE
    const mockOnOpenChange = vi.fn();
    renderHook(() => useGlobalSearchHotkey({ onOpenChange: mockOnOpenChange }), {
      wrapper: ({ children }: { children: ReactNode }) => children,
    });

    // ACT
    const event = new KeyboardEvent('keydown', {
      code: 'KeyK',
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

  it.each(hotkeyCases)('$description', ({ eventInit, expectedOpenChange }) => {
    // ARRANGE
    const mockOnOpenChange = vi.fn();
    renderHook(() => useGlobalSearchHotkey({ onOpenChange: mockOnOpenChange }), {
      wrapper: ({ children }: { children: ReactNode }) => children,
    });

    // ACT
    act(() => {
      document.dispatchEvent(new KeyboardEvent('keydown', eventInit));
    });

    // ASSERT
    if (expectedOpenChange) {
      expect(mockOnOpenChange).toHaveBeenCalledTimes(1);
      expect(mockOnOpenChange).toHaveBeenCalledWith(true);
    } else {
      expect(mockOnOpenChange).not.toHaveBeenCalled();
    }
  });
});
