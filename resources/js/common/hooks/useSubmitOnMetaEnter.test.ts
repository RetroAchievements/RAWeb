import { useRef } from 'react';

import { renderHook } from '@/test';

import { useSubmitOnMetaEnter } from './useSubmitOnMetaEnter';

describe('Hook: useSubmitOnMetaEnter', () => {
  let formElement: HTMLFormElement;
  let inputElement: HTMLInputElement;
  let outsideElement: HTMLDivElement;
  let onSubmitSpy: ReturnType<typeof vi.fn>;

  beforeEach(() => {
    // ... create a form with an input inside ...
    formElement = document.createElement('form');
    inputElement = document.createElement('input');
    formElement.appendChild(inputElement);
    document.body.appendChild(formElement);

    // ... create an element outside the form ...
    outsideElement = document.createElement('div');
    document.body.appendChild(outsideElement);

    onSubmitSpy = vi.fn();
  });

  afterEach(() => {
    // ... clean up DOM elements ...
    document.body.removeChild(formElement);
    document.body.removeChild(outsideElement);

    vi.clearAllMocks();
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { result } = renderHook(() => {
      const formRef = useRef<HTMLFormElement>(null);
      useSubmitOnMetaEnter({ formRef, onSubmit: onSubmitSpy });

      return formRef;
    });

    // ASSERT
    expect(result.current).toBeDefined();
  });

  it('given the user presses Cmd+Enter while focused within the form, calls onSubmit', () => {
    // ARRANGE
    renderHook(() => {
      const formRef = useRef<HTMLFormElement>(formElement);
      useSubmitOnMetaEnter({ formRef, onSubmit: onSubmitSpy });
    });

    // ... focus on the input inside the form ...
    inputElement.focus();

    // ACT
    const event = new KeyboardEvent('keydown', {
      metaKey: true,
      code: 'Enter',
      bubbles: true,
    });
    document.dispatchEvent(event);

    // ASSERT
    expect(onSubmitSpy).toHaveBeenCalledOnce();
  });

  it('given the user presses Ctrl+Enter while focused within the form, calls onSubmit', () => {
    // ARRANGE
    renderHook(() => {
      const formRef = useRef<HTMLFormElement>(formElement);
      useSubmitOnMetaEnter({ formRef, onSubmit: onSubmitSpy });
    });

    // ... focus on the input inside the form ...
    inputElement.focus();

    // ACT
    const event = new KeyboardEvent('keydown', {
      ctrlKey: true,
      code: 'Enter',
      bubbles: true,
    });
    document.dispatchEvent(event);

    // ASSERT
    expect(onSubmitSpy).toHaveBeenCalledOnce();
  });

  it('given the user presses Cmd+Enter while focused outside the form, does not call onSubmit', () => {
    // ARRANGE
    renderHook(() => {
      const formRef = useRef<HTMLFormElement>(formElement);
      useSubmitOnMetaEnter({ formRef, onSubmit: onSubmitSpy });
    });

    // ... focus on an element outside the form ...
    outsideElement.focus();

    // ACT
    const event = new KeyboardEvent('keydown', {
      metaKey: true,
      code: 'Enter',
      bubbles: true,
    });
    document.dispatchEvent(event);

    // ASSERT
    expect(onSubmitSpy).not.toHaveBeenCalled();
  });

  it('given isEnabled is false, does not call onSubmit when Cmd+Enter is pressed', () => {
    // ARRANGE
    renderHook(() => {
      const formRef = useRef<HTMLFormElement>(formElement);
      useSubmitOnMetaEnter({ formRef, onSubmit: onSubmitSpy, isEnabled: false });
    });

    // ... focus on the input inside the form ...
    inputElement.focus();

    // ACT
    const event = new KeyboardEvent('keydown', {
      metaKey: true,
      code: 'Enter',
      bubbles: true,
    });
    document.dispatchEvent(event);

    // ASSERT
    expect(onSubmitSpy).not.toHaveBeenCalled();
  });

  it('given the user presses only Enter without modifier keys, does not call onSubmit', () => {
    // ARRANGE
    renderHook(() => {
      const formRef = useRef<HTMLFormElement>(formElement);
      useSubmitOnMetaEnter({ formRef, onSubmit: onSubmitSpy });
    });

    // ... focus on the input inside the form ...
    inputElement.focus();

    // ACT
    const event = new KeyboardEvent('keydown', {
      code: 'Enter',
      bubbles: true,
    });
    document.dispatchEvent(event);

    // ASSERT
    expect(onSubmitSpy).not.toHaveBeenCalled();
  });

  it('given the user presses Cmd+A while focused within the form, does not call onSubmit', () => {
    // ARRANGE
    renderHook(() => {
      const formRef = useRef<HTMLFormElement>(formElement);
      useSubmitOnMetaEnter({ formRef, onSubmit: onSubmitSpy });
    });

    // ... focus on the input inside the form ...
    inputElement.focus();

    // ACT
    const event = new KeyboardEvent('keydown', {
      metaKey: true,
      code: 'KeyA',
      bubbles: true,
    });
    document.dispatchEvent(event);

    // ASSERT
    expect(onSubmitSpy).not.toHaveBeenCalled();
  });

  it('given the formRef is null, does not call onSubmit when Cmd+Enter is pressed', () => {
    // ARRANGE
    renderHook(() => {
      const formRef = useRef<HTMLFormElement>(null);
      useSubmitOnMetaEnter({ formRef, onSubmit: onSubmitSpy });
    });

    // ACT
    const event = new KeyboardEvent('keydown', {
      metaKey: true,
      code: 'Enter',
      bubbles: true,
    });
    document.dispatchEvent(event);

    // ASSERT
    expect(onSubmitSpy).not.toHaveBeenCalled();
  });

  it('given the event listener is set up, prevents default when Cmd+Enter is pressed within the form', () => {
    // ARRANGE
    renderHook(() => {
      const formRef = useRef<HTMLFormElement>(formElement);
      useSubmitOnMetaEnter({ formRef, onSubmit: onSubmitSpy });
    });

    // ... focus on the input inside the form ...
    inputElement.focus();

    const event = new KeyboardEvent('keydown', {
      metaKey: true,
      code: 'Enter',
      bubbles: true,
    });
    const preventDefaultSpy = vi.spyOn(event, 'preventDefault');

    // ACT
    document.dispatchEvent(event);

    // ASSERT
    expect(preventDefaultSpy).toHaveBeenCalledOnce();
  });
});
