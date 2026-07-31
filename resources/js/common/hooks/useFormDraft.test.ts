import { useForm } from 'react-hook-form';

import { act, renderHook } from '@/test';

import { useFormDraft } from './useFormDraft';

interface FormValues {
  body: string;
}

const baseDefaultValues: FormValues = { body: '' };

function renderFormDraftHook(initialKey: string | null) {
  return renderHook(
    ({ draftKey }: { draftKey: string | null }) => {
      const form = useForm<FormValues>({ defaultValues: baseDefaultValues });

      return { form, ...useFormDraft(draftKey, form, baseDefaultValues) };
    },
    { initialProps: { draftKey: initialKey } },
  );
}

describe('Hook: useFormDraft', () => {
  beforeEach(() => {
    sessionStorage.clear();
    vi.useFakeTimers();
  });

  afterEach(() => {
    vi.useRealTimers();
    vi.unstubAllGlobals();
  });

  function stubUnavailableStorage() {
    vi.stubGlobal('sessionStorage', {
      getItem: () => null,
      setItem: () => {
        throw new Error('QuotaExceededError');
      },
      removeItem: () => {
        throw new Error('SecurityError');
      },
    });
  }

  it('renders without crashing', () => {
    // ARRANGE
    const { result } = renderFormDraftHook('draft-key');

    // ASSERT
    expect(typeof result.current.clearDraft).toEqual('function');
  });

  it('given the user changes a value, debounces writing the draft to sessionStorage', () => {
    // ARRANGE
    const { result } = renderFormDraftHook('draft-key');

    // ACT
    act(() => {
      result.current.form.setValue('body', 'hello');
    });

    // ASSERT
    // ... nothing is written until the debounce elapses ...
    expect(sessionStorage.getItem('draft-key')).toBeNull();

    // ACT
    act(() => {
      vi.advanceTimersByTime(500);
    });

    // ASSERT
    expect(sessionStorage.getItem('draft-key')).toEqual(JSON.stringify({ body: 'hello' }));
  });

  it('given the form unmounts before the debounce elapses, still writes the draft', () => {
    // ARRANGE
    const { result, unmount } = renderFormDraftHook('draft-key');

    act(() => {
      result.current.form.setValue('body', 'hello');
    });

    // ... sanity check - the debounced write hasn't happened yet ...
    expect(sessionStorage.getItem('draft-key')).toBeNull();

    // ACT
    unmount();

    // ASSERT
    expect(sessionStorage.getItem('draft-key')).toEqual(JSON.stringify({ body: 'hello' }));
  });

  it('given a null key, never persists anything', () => {
    // ARRANGE
    const { result, unmount } = renderFormDraftHook(null);

    // ACT
    act(() => {
      result.current.form.setValue('body', 'hello');
    });
    act(() => {
      vi.advanceTimersByTime(500);
    });
    act(() => {
      result.current.clearDraft();
    });
    unmount();

    // ASSERT
    expect(sessionStorage.length).toEqual(0);
  });

  it('given clearDraft is called, removes the draft and suppresses the pending write', () => {
    // ARRANGE
    const { result, unmount } = renderFormDraftHook('draft-key');

    act(() => {
      result.current.form.setValue('body', 'hello');
    });

    // ACT
    act(() => {
      result.current.clearDraft();
    });
    act(() => {
      vi.advanceTimersByTime(500);
    });
    unmount();

    // ASSERT
    expect(sessionStorage.getItem('draft-key')).toBeNull();
  });

  it("given the key changes, rehydrates the form with the new key's draft", () => {
    // ARRANGE
    sessionStorage.setItem('draft-key-2', JSON.stringify({ body: 'the other draft' }));

    const { result, rerender } = renderFormDraftHook('draft-key-1');

    // ACT
    rerender({ draftKey: 'draft-key-2' });

    // ASSERT
    expect(result.current.form.getValues('body')).toEqual('the other draft');
  });

  it('given the key changes and the new key has no draft, resets the form to its base defaults', () => {
    // ARRANGE
    const { result, rerender } = renderFormDraftHook('draft-key-1');

    act(() => {
      result.current.form.setValue('body', 'hello');
    });

    // ACT
    rerender({ draftKey: 'draft-key-2' });

    // ASSERT
    expect(result.current.form.getValues('body')).toEqual('');
  });

  it('given the key changes while a write is pending, persists it under the old key', () => {
    // ARRANGE
    const { result, rerender } = renderFormDraftHook('draft-key-1');

    act(() => {
      result.current.form.setValue('body', 'hello');
    });

    // ACT
    rerender({ draftKey: 'draft-key-2' });

    // ASSERT
    expect(sessionStorage.getItem('draft-key-1')).toEqual(JSON.stringify({ body: 'hello' }));
  });

  it('given the key is unchanged on a rerender, leaves the current values alone', () => {
    // ARRANGE
    const { result, rerender } = renderFormDraftHook('draft-key');

    act(() => {
      result.current.form.setValue('body', 'hello');
    });

    // ACT
    rerender({ draftKey: 'draft-key' });

    // ASSERT
    expect(result.current.form.getValues('body')).toEqual('hello');
  });

  it('given the browser refuses to store the draft, keeps the failure contained', () => {
    // ARRANGE
    stubUnavailableStorage();

    const { result } = renderFormDraftHook('draft-key');

    act(() => {
      result.current.form.setValue('body', 'hello');
    });

    // ACT & ASSERT
    expect(() => {
      act(() => {
        vi.advanceTimersByTime(500);
      });
    }).not.toThrow();
  });

  it('given the browser refuses to store the draft, still unmounts cleanly', () => {
    // ARRANGE
    stubUnavailableStorage();

    const { result, unmount } = renderFormDraftHook('draft-key');

    act(() => {
      result.current.form.setValue('body', 'hello');
    });

    // ACT & ASSERT
    expect(() => unmount()).not.toThrow();
  });

  it('given the browser refuses to remove the draft, keeps the failure contained', () => {
    // ARRANGE
    stubUnavailableStorage();

    const { result } = renderFormDraftHook('draft-key');

    // ACT & ASSERT
    expect(() => {
      act(() => {
        result.current.clearDraft();
      });
    }).not.toThrow();
  });

  it('given the key becomes null, leaves the current values alone', () => {
    // ARRANGE
    const { result, rerender } = renderFormDraftHook('draft-key');

    act(() => {
      result.current.form.setValue('body', 'hello');
    });

    // ACT
    rerender({ draftKey: null });

    // ASSERT
    expect(result.current.form.getValues('body')).toEqual('hello');
  });
});
