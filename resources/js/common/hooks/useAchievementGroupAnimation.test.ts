import { act, renderHook } from '@/test';

import { useAchievementGroupAnimation } from './useAchievementGroupAnimation';

/**
 * happy-dom's TransitionEvent ignores the `propertyName` init option.
 * Attach it to a plain bubbling event instead.
 */
function dispatchTransitionEnd(target: Element, propertyName: string) {
  const event = Object.assign(new Event('transitionend', { bubbles: true }), { propertyName });

  act(() => {
    target.dispatchEvent(event);
  });
}

describe('Hook: useAchievementGroupAnimation', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { result } = renderHook(() => useAchievementGroupAnimation({ isInitiallyOpened: false }));

    // ASSERT
    expect(result.current).toBeTruthy();
    expect(result.current.isOpen).toEqual(false);
    expect(result.current.isInitialRender.current).toEqual(false);
  });

  it('given isInitiallyOpened is true, initializes with open state', () => {
    // ARRANGE
    const { result } = renderHook(() => useAchievementGroupAnimation({ isInitiallyOpened: true }));

    // ASSERT
    expect(result.current.isOpen).toEqual(true);
  });

  it('given isInitiallyOpened is false, applies initial closed styling on mount', () => {
    // ARRANGE
    const contentElement = document.createElement('div');

    // ... use a factory to prepare the hook with mocked refs ...
    const useTestHook = () => {
      const hook = useAchievementGroupAnimation({ isInitiallyOpened: false });

      // ... mock the contentRef with our prepared element - this runs during initialization ...
      if (!hook.contentRef.current) {
        Object.defineProperty(hook.contentRef, 'current', {
          value: contentElement,
          writable: true,
        });
      }

      return hook;
    };

    // ACT
    renderHook(() => useTestHook());

    // ASSERT
    expect(contentElement.style.height).toEqual('0px');
    expect(contentElement.style.overflow).toEqual('hidden');
    expect(contentElement.style.opacity).toEqual('0');
  });

  it('given null refs, toggling does not throw', () => {
    // ARRANGE
    const { result } = renderHook(() => useAchievementGroupAnimation({ isInitiallyOpened: false }));

    act(() => {
      result.current.isInitialRender.current = false;
    });

    // ASSERT
    expect(() => {
      act(() => {
        result.current.setIsOpen(true);
      });
    }).not.toThrow();
    expect(result.current.isOpen).toEqual(true);
  });

  it('given opening animation, sets and transitions proper styles', () => {
    // ARRANGE
    vi.useFakeTimers();

    const { result } = renderHook(() => useAchievementGroupAnimation({ isInitiallyOpened: false }));

    // ... skip initial render ...
    act(() => {
      result.current.isInitialRender.current = false;
    });

    // ... create mocked DOM elements ...
    const contentElement = document.createElement('div');
    const childElement = document.createElement('ul');

    // ... mock scrollHeight to simulate content height ...
    Object.defineProperty(childElement, 'scrollHeight', { value: 100 });

    // ... record the starting styles the hook commits with its forced reflow ...
    let stylesAtReflow = { height: '', opacity: '' };
    Object.defineProperty(contentElement, 'offsetHeight', {
      get: () => {
        stylesAtReflow = {
          height: contentElement.style.height,
          opacity: contentElement.style.opacity,
        };

        return 0;
      },
    });

    // ... mock refs ...
    Object.defineProperty(result.current.contentRef, 'current', {
      value: contentElement,
      writable: true,
    });

    Object.defineProperty(result.current.childContainerRef, 'current', {
      value: childElement,
      writable: true,
    });

    // ACT
    act(() => {
      result.current.setIsOpen(true);
    });

    // ASSERT
    // ... the animation starts from a collapsed, transparent state ...
    expect(stylesAtReflow).toEqual({ height: '0px', opacity: '0' });

    // ... the height stays collapsed while opacity fades in ...
    expect(contentElement.style.height).toEqual('0px');
    expect(contentElement.style.overflow).toEqual('hidden');
    expect(contentElement.style.opacity).toEqual('1');
    expect(contentElement.style.transition).toContain('height 350ms cubic-bezier(0.2, 0, 0, 1)');
    expect(contentElement.style.transition).toContain('opacity');

    // ... the height will be set with a setTimeout, so we need to advance timers ...
    act(() => {
      vi.advanceTimersByTime(10);
    });
    expect(contentElement.style.height).toEqual('100px');

    // ... simulate transition end for the height property ...
    dispatchTransitionEnd(contentElement, 'height');

    // ... check for added buffer and overflow visible ...
    expect(contentElement.style.height).toEqual('104px'); // 100 + 4 buffer
    expect(contentElement.style.overflow).toEqual('visible');

    // ... the listener is removed, so another transition end does not add more buffer ...
    dispatchTransitionEnd(contentElement, 'height');
    expect(contentElement.style.height).toEqual('104px');
  });

  it('given closing animation, properly transitions to closed state', () => {
    // ARRANGE
    const { result } = renderHook(() => useAchievementGroupAnimation({ isInitiallyOpened: true }));

    // ... skip initial render ...
    act(() => {
      result.current.isInitialRender.current = false;
    });

    // ... create mocked DOM elements ...
    const contentElement = document.createElement('div');
    const childElement = document.createElement('ul');

    Object.defineProperty(childElement, 'scrollHeight', { value: 100 });

    // ... record the starting styles the hook commits with its forced reflow ...
    let stylesAtReflow = { height: '', opacity: '' };
    Object.defineProperty(contentElement, 'offsetHeight', {
      get: () => {
        stylesAtReflow = {
          height: contentElement.style.height,
          opacity: contentElement.style.opacity,
        };

        return 0;
      },
    });

    // ... mock refs ...
    Object.defineProperty(result.current.contentRef, 'current', {
      value: contentElement,
      writable: true,
    });

    Object.defineProperty(result.current.childContainerRef, 'current', {
      value: childElement,
      writable: true,
    });

    // ACT
    act(() => {
      result.current.setIsOpen(false);
    });

    // ASSERT
    // ... the animation starts from the full content height, so the browser can transition it ...
    expect(stylesAtReflow).toEqual({ height: '100px', opacity: '1' });

    expect(contentElement.style.height).toEqual('0px');
    expect(contentElement.style.overflow).toEqual('hidden');
    expect(contentElement.style.opacity).toEqual('0');
    expect(contentElement.style.transition).toContain('height 350ms cubic-bezier(0.2, 0, 0, 1)');
    expect(contentElement.style.transition).toContain('opacity');

    // ... closing does not add the open-state height buffer or reveal overflow ...
    dispatchTransitionEnd(contentElement, 'height');
    expect(contentElement.style.height).toEqual('0px');
    expect(contentElement.style.overflow).toEqual('hidden');
  });

  it('given a transition event for a non-height property, does not apply post-transition adjustments', () => {
    // ARRANGE
    vi.useFakeTimers();

    const { result } = renderHook(() => useAchievementGroupAnimation({ isInitiallyOpened: false }));

    // ... skip initial render ...
    act(() => {
      result.current.isInitialRender.current = false;
    });

    // ... create mocked DOM elements ...
    const contentElement = document.createElement('div');
    const childElement = document.createElement('ul');

    Object.defineProperty(childElement, 'scrollHeight', { value: 100 });

    // ... mock refs ...
    Object.defineProperty(result.current.contentRef, 'current', {
      value: contentElement,
      writable: true,
    });

    Object.defineProperty(result.current.childContainerRef, 'current', {
      value: childElement,
      writable: true,
    });

    act(() => {
      result.current.setIsOpen(true);
    });

    act(() => {
      vi.advanceTimersByTime(10);
    });

    // ACT
    dispatchTransitionEnd(contentElement, 'opacity'); // !! not height!

    // ASSERT
    // ... should not adjust height or overflow for non-height transitions ...
    expect(contentElement.style.height).toEqual('100px');
    expect(contentElement.style.overflow).toEqual('hidden');
  });

  it('given a transition event from another element, does not apply post-transition adjustments', () => {
    // ARRANGE
    vi.useFakeTimers();

    const { result } = renderHook(() => useAchievementGroupAnimation({ isInitiallyOpened: false }));

    // ... skip initial render ...
    act(() => {
      result.current.isInitialRender.current = false;
    });

    // ... create mocked DOM elements ...
    const contentElement = document.createElement('div');
    const childElement = document.createElement('ul');
    const nestedElement = document.createElement('div');
    contentElement.appendChild(nestedElement);

    Object.defineProperty(childElement, 'scrollHeight', { value: 100 });

    // ... mock refs ...
    Object.defineProperty(result.current.contentRef, 'current', {
      value: contentElement,
      writable: true,
    });

    Object.defineProperty(result.current.childContainerRef, 'current', {
      value: childElement,
      writable: true,
    });

    act(() => {
      result.current.setIsOpen(true);
    });

    act(() => {
      vi.advanceTimersByTime(10);
    });

    // ACT
    // ... a nested element's height transition bubbles up to the content element ...
    dispatchTransitionEnd(nestedElement, 'height'); // !! different element!

    // ASSERT
    // ... should not adjust height or overflow for events from other elements ...
    expect(contentElement.style.height).toEqual('100px');
    expect(contentElement.style.overflow).toEqual('hidden');
  });
});
