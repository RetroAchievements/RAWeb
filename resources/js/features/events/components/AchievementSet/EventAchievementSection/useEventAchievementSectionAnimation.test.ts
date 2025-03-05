import { act, renderHook } from '@/test';

import { useEventAchievementSectionAnimation } from './useEventAchievementSectionAnimation';

describe('Hook: useEventAchievementSectionAnimation', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { result } = renderHook(() =>
      useEventAchievementSectionAnimation({ isInitiallyOpened: false }),
    );

    // ASSERT
    expect(result.current).toBeTruthy();
    expect(result.current.isOpen).toEqual(false);
    expect(result.current.isInitialRender.current).toEqual(false);
  });

  it('given isInitiallyOpened is true, initializes with open state', () => {
    // ARRANGE
    const { result } = renderHook(() =>
      useEventAchievementSectionAnimation({ isInitiallyOpened: true }),
    );

    // ASSERT
    expect(result.current.isOpen).toEqual(true);
  });

  it('given null refs, does not attempt to animate', () => {
    // ARRANGE
    const { result } = renderHook(() =>
      useEventAchievementSectionAnimation({ isInitiallyOpened: false }),
    );

    // ... create a mock element to track if its style would be modified ...
    const mockElement = document.createElement('div');
    const styleSpy = vi.spyOn(mockElement.style, 'height', 'set');

    // ... ensure refs are null (they are by default in JSDOM) ...
    expect(result.current.contentRef.current).toEqual(null);
    expect(result.current.childContainerRef.current).toEqual(null);

    // ACT
    // ... skip initial render ...
    act(() => {
      result.current.isInitialRender.current = false;
    });

    // ... trigger animation ...
    act(() => {
      result.current.setIsOpen(true);
    });

    // ASSERT
    // ... style should not be accessed since we returned early ...
    expect(styleSpy).not.toHaveBeenCalled();
  });

  it('given isInitiallyOpened is false, applies initial closed styling on mount', () => {
    // ARRANGE
    const contentElement = document.createElement('div');
    const heightSpy = vi.spyOn(contentElement.style, 'height', 'set');
    const overflowSpy = vi.spyOn(contentElement.style, 'overflow', 'set');
    const opacitySpy = vi.spyOn(contentElement.style, 'opacity', 'set');

    // ... use a factory to prepare the hook with mocked refs ...
    const useTestHook = () => {
      const hook = useEventAchievementSectionAnimation({ isInitiallyOpened: false });

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
    expect(heightSpy).toHaveBeenCalledWith('0px');
    expect(overflowSpy).toHaveBeenCalledWith('hidden');
    expect(opacitySpy).toHaveBeenCalledWith('0');
  });

  it('given opening animation, sets and transitions proper styles', () => {
    // ARRANGE
    vi.useFakeTimers();

    const { result } = renderHook(() =>
      useEventAchievementSectionAnimation({ isInitiallyOpened: false }),
    );

    // ... skip initial render ...
    act(() => {
      result.current.isInitialRender.current = false;
    });

    // ... create mocked DOM elements ...
    const contentElement = document.createElement('div');
    const childElement = document.createElement('ul');

    // ... mock scrollHeight to simulate content height ...
    Object.defineProperty(childElement, 'scrollHeight', { value: 100 });

    // ... create spies for style properties ...
    const heightSpy = vi.spyOn(contentElement.style, 'height', 'set');
    const overflowSpy = vi.spyOn(contentElement.style, 'overflow', 'set');
    const opacitySpy = vi.spyOn(contentElement.style, 'opacity', 'set');
    const transitionSpy = vi.spyOn(contentElement.style, 'transition', 'set');
    const addEventSpy = vi.spyOn(contentElement, 'addEventListener');
    const removeEventSpy = vi.spyOn(contentElement, 'removeEventListener');

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
    expect(heightSpy).toHaveBeenCalledWith('0px');
    expect(overflowSpy).toHaveBeenCalledWith('hidden');
    expect(opacitySpy).toHaveBeenCalledWith('0');

    expect(transitionSpy).toHaveBeenCalledWith(
      expect.stringContaining('height 350ms cubic-bezier(0.2, 0, 0, 1)'),
    );
    expect(transitionSpy).toHaveBeenCalledWith(expect.stringContaining('opacity'));

    // !! verify opacity is set to 1 for opening.
    expect(opacitySpy).toHaveBeenCalledWith('1');

    // ... the height will be set with a setTimeout, so we need to advance timers ...
    vi.advanceTimersByTime(10);
    expect(heightSpy).toHaveBeenCalledWith('100px');

    expect(addEventSpy).toHaveBeenCalledWith('transitionend', expect.any(Function));

    // ... simulate transition end for height property only ...
    const transitionHandler = addEventSpy.mock.calls[0][1] as EventListener;
    const mockTransitionEvent = {
      propertyName: 'height',
      target: contentElement,
    } as unknown as TransitionEvent;

    act(() => {
      transitionHandler(mockTransitionEvent);
    });

    // ... check for added buffer and overflow visible ...
    expect(heightSpy).toHaveBeenCalledWith('104px'); // 100 + 4 buffer
    expect(overflowSpy).toHaveBeenCalledWith('visible');
    expect(removeEventSpy).toHaveBeenCalledWith('transitionend', transitionHandler);
  });

  it('given closing animation, properly transitions to closed state', () => {
    // ARRANGE
    const { result } = renderHook(() =>
      useEventAchievementSectionAnimation({ isInitiallyOpened: true }),
    );

    // ... skip initial render ...
    act(() => {
      result.current.isInitialRender.current = false;
    });

    // ... create mocked DOM elements ...
    const contentElement = document.createElement('div');
    const childElement = document.createElement('ul');

    Object.defineProperty(childElement, 'scrollHeight', { value: 100 });

    // ... create spies for style properties ...
    const heightSpy = vi.spyOn(contentElement.style, 'height', 'set');
    const overflowSpy = vi.spyOn(contentElement.style, 'overflow', 'set');
    const opacitySpy = vi.spyOn(contentElement.style, 'opacity', 'set');
    const transitionSpy = vi.spyOn(contentElement.style, 'transition', 'set');
    const addEventSpy = vi.spyOn(contentElement, 'addEventListener');
    const removeEventSpy = vi.spyOn(contentElement, 'removeEventListener');

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
    expect(heightSpy).toHaveBeenCalledWith('100px');
    expect(overflowSpy).toHaveBeenCalledWith('hidden');
    expect(opacitySpy).toHaveBeenCalledWith('1');

    // Check for appropriate transitions with the new easing
    expect(transitionSpy).toHaveBeenCalledWith(
      expect.stringContaining('height 350ms cubic-bezier(0.2, 0, 0, 1)'),
    );
    expect(transitionSpy).toHaveBeenCalledWith(expect.stringContaining('opacity'));

    expect(heightSpy).toHaveBeenCalledWith('0px');
    expect(opacitySpy).toHaveBeenCalledWith('0');

    expect(addEventSpy).toHaveBeenCalledWith('transitionend', expect.any(Function));

    // ... simulate transition end for height property ...
    const transitionHandler = addEventSpy.mock.calls[0][1] as EventListener;
    const mockTransitionEvent = {
      propertyName: 'height',
      target: contentElement,
    } as unknown as TransitionEvent;

    act(() => {
      transitionHandler(mockTransitionEvent);
    });

    // ... verify event listener cleanup ...
    expect(removeEventSpy).toHaveBeenCalledWith('transitionend', transitionHandler);
  });

  it('given a transition event for a non-height property, does not apply post-transition adjustments', () => {
    // ARRANGE
    const { result } = renderHook(() =>
      useEventAchievementSectionAnimation({ isInitiallyOpened: false }),
    );

    // ... skip initial render ...
    act(() => {
      result.current.isInitialRender.current = false;
    });

    // ... create mocked DOM elements ...
    const contentElement = document.createElement('div');
    const childElement = document.createElement('ul');

    Object.defineProperty(childElement, 'scrollHeight', { value: 100 });

    // ... create spies ...
    const heightSpy = vi.spyOn(contentElement.style, 'height', 'set');
    const overflowSpy = vi.spyOn(contentElement.style, 'overflow', 'set');
    const addEventSpy = vi.spyOn(contentElement, 'addEventListener');

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

    // !! clear the spies to track only post-transition calls.
    heightSpy.mockClear();
    overflowSpy.mockClear();

    // ... simulate transition end for opacity property (not height) ...
    const transitionHandler = addEventSpy.mock.calls[0][1] as EventListener;
    const mockTransitionEvent = {
      propertyName: 'opacity', // !! not height!
      target: contentElement,
    } as unknown as TransitionEvent;

    act(() => {
      transitionHandler(mockTransitionEvent);
    });

    // ASSERT
    // ... should not adjust height or overflow for non-height transitions ...
    expect(heightSpy).not.toHaveBeenCalled();
    expect(overflowSpy).not.toHaveBeenCalled();
  });

  it('given a transition event from another element, does not apply post-transition adjustments', () => {
    // ARRANGE
    const { result } = renderHook(() =>
      useEventAchievementSectionAnimation({ isInitiallyOpened: false }),
    );

    // ... skip initial render ...
    act(() => {
      result.current.isInitialRender.current = false;
    });

    // ... create mocked DOM elements ...
    const contentElement = document.createElement('div');
    const childElement = document.createElement('ul');
    const otherElement = document.createElement('div'); // Different element

    Object.defineProperty(childElement, 'scrollHeight', { value: 100 });

    // ... create spies ...
    const heightSpy = vi.spyOn(contentElement.style, 'height', 'set');
    const overflowSpy = vi.spyOn(contentElement.style, 'overflow', 'set');
    const addEventSpy = vi.spyOn(contentElement, 'addEventListener');

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

    // ... clear the spies to track only post-transition calls ...
    heightSpy.mockClear();
    overflowSpy.mockClear();

    // ... simulate transition end from a different element ...
    const transitionHandler = addEventSpy.mock.calls[0][1] as EventListener;
    const mockTransitionEvent = {
      propertyName: 'height',
      target: otherElement, // !! different element!
    } as unknown as TransitionEvent;

    act(() => {
      transitionHandler(mockTransitionEvent);
    });

    // ASSERT
    // ... should not adjust height or overflow for events from other elements ...
    expect(heightSpy).not.toHaveBeenCalled();
    expect(overflowSpy).not.toHaveBeenCalled();
  });
});
