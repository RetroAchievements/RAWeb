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

  it('given opening animation, sets and cleans up styles after transition', () => {
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

    Object.defineProperty(childElement, 'offsetHeight', { value: 100 });

    // ... create spies for style properties ...
    const heightSpy = vi.spyOn(contentElement.style, 'height', 'set');
    const overflowSpy = vi.spyOn(contentElement.style, 'overflow', 'set');
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
    expect(heightSpy).toHaveBeenCalledWith('100px');
    expect(overflowSpy).toHaveBeenCalledWith('hidden');
    expect(transitionSpy).toHaveBeenCalledWith('height 0.3s cubic-bezier(0.4, 0, 0.2, 1)');
    expect(addEventSpy).toHaveBeenCalledWith('transitionend', expect.any(Function));

    // ... simulate transition end ...
    const transitionHandler = addEventSpy.mock.calls[0][1] as EventListener;
    act(() => {
      transitionHandler({} as Event);
    });

    // ... now do cleanup checks ...
    expect(heightSpy).toHaveBeenCalledWith('');
    expect(overflowSpy).toHaveBeenCalledWith('');
    expect(transitionSpy).toHaveBeenCalledWith('');
    expect(removeEventSpy).toHaveBeenCalledWith('transitionend', transitionHandler);
  });

  it('given closing animation, sets and keeps some styles after transition', () => {
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

    Object.defineProperty(childElement, 'offsetHeight', { value: 100 });

    // ... create spies for style properties ...
    const heightSpy = vi.spyOn(contentElement.style, 'height', 'set');
    const overflowSpy = vi.spyOn(contentElement.style, 'overflow', 'set');
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
    expect(heightSpy).toHaveBeenCalledWith('0px');
    expect(overflowSpy).toHaveBeenCalledWith('hidden');
    expect(transitionSpy).toHaveBeenCalledWith('height 0.3s cubic-bezier(0.4, 0, 0.2, 1)');
    expect(addEventSpy).toHaveBeenCalledWith('transitionend', expect.any(Function));

    // ... simulate transition end ...
    const transitionHandler = addEventSpy.mock.calls[0][1] as EventListener;
    act(() => {
      transitionHandler({} as Event);
    });

    // ... now do cleanup checks - note that for closing, only transition is reset! ...
    expect(transitionSpy).toHaveBeenCalledWith('');
    expect(removeEventSpy).toHaveBeenCalledWith('transitionend', transitionHandler);

    // ... these are NOT reset for the closing transition ...
    expect(heightSpy).not.toHaveBeenCalledWith('');
    expect(overflowSpy).not.toHaveBeenCalledWith('');
  });
});
