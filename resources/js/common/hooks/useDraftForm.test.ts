import { createAuthenticatedUser } from '@/common/models';
import { act, renderHook } from '@/test';

import { useDraftForm } from './useDraftForm';

interface FormValues {
  body: string;
}

const baseDefaultValues: FormValues = { body: '' };

function renderDraftFormHook(initialKey: string | null, isSignedIn = true) {
  return renderHook(
    ({ draftKey }: { draftKey: string | null }) =>
      useDraftForm<FormValues>(draftKey, { defaultValues: baseDefaultValues }),
    {
      initialProps: { draftKey: initialKey },
      pageProps: {
        auth: isSignedIn ? { user: createAuthenticatedUser({ id: 7 }) } : null,
      },
    },
  );
}

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

describe('Hook: useDraftForm', () => {
  beforeEach(() => {
    sessionStorage.clear();
    vi.useFakeTimers();
  });

  afterEach(() => {
    vi.useRealTimers();
    vi.unstubAllGlobals();
  });

  it('renders without crashing', () => {
    // ARRANGE
    const { result } = renderDraftFormHook('draft-key');

    // ASSERT
    expect(result.current.form).toBeTruthy();
    expect(typeof result.current.clearDraft).toEqual('function');
  });

  it('given the user changes a value, debounces writing the draft under a key scoped to them', () => {
    // ARRANGE
    const { result } = renderDraftFormHook('draft-key');

    // ACT
    act(() => {
      result.current.form.setValue('body', 'hello');
    });

    // ASSERT
    // ... nothing is written until the debounce elapses ...
    expect(sessionStorage.getItem('draft-key-7')).toBeNull();

    // ACT
    act(() => {
      vi.advanceTimersByTime(500);
    });

    // ASSERT
    expect(sessionStorage.getItem('draft-key-7')).toEqual(JSON.stringify({ body: 'hello' }));
  });

  it('given a stored draft, hydrates the form on mount', () => {
    // ARRANGE
    sessionStorage.setItem('draft-key-7', JSON.stringify({ body: 'from storage' }));

    // ACT
    const { result } = renderDraftFormHook('draft-key');

    // ASSERT
    expect(result.current.form.getValues('body')).toEqual('from storage');
  });

  it('given the stored draft is corrupted JSON, falls back to the default values', () => {
    // ARRANGE
    sessionStorage.setItem('draft-key-7', '{not valid json');

    // ACT
    const { result } = renderDraftFormHook('draft-key');

    // ASSERT
    expect(result.current.form.getValues('body')).toEqual('');
  });

  it('given the form unmounts before the debounce elapses, still writes the draft', () => {
    // ARRANGE
    const { result, unmount } = renderDraftFormHook('draft-key');

    act(() => {
      result.current.form.setValue('body', 'hello');
    });

    // ... sanity check - the debounced write hasn't happened yet ...
    expect(sessionStorage.getItem('draft-key-7')).toBeNull();

    // ACT
    unmount();

    // ASSERT
    expect(sessionStorage.getItem('draft-key-7')).toEqual(JSON.stringify({ body: 'hello' }));
  });

  it('given a null key, never persists anything', () => {
    // ARRANGE
    const { result, unmount } = renderDraftFormHook(null);

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

  it('given nobody is signed in, never persists anything', () => {
    // ARRANGE
    const { result, unmount } = renderDraftFormHook('draft-key', false);

    // ACT
    act(() => {
      result.current.form.setValue('body', 'hello');
    });
    act(() => {
      vi.advanceTimersByTime(500);
    });
    unmount();

    // ASSERT
    expect(sessionStorage.length).toEqual(0);
  });

  it('given clearDraft is called, removes the entry and resets the form without writing it back', () => {
    // ARRANGE
    const { result } = renderDraftFormHook('draft-key');

    act(() => {
      result.current.form.setValue('body', 'hello');
    });
    act(() => {
      vi.advanceTimersByTime(500);
    });

    // ... sanity check - the draft made it to storage ...
    expect(sessionStorage.getItem('draft-key-7')).toBeTruthy();

    // ACT
    act(() => {
      result.current.clearDraft();
    });
    act(() => {
      vi.advanceTimersByTime(500);
    });

    // ASSERT
    expect(sessionStorage.getItem('draft-key-7')).toBeNull();
    expect(result.current.form.getValues('body')).toEqual('');
  });

  it('given the user empties the form themselves, drops the stored entry', () => {
    // ARRANGE
    const { result } = renderDraftFormHook('draft-key');

    act(() => {
      result.current.form.setValue('body', 'hello');
    });
    act(() => {
      vi.advanceTimersByTime(500);
    });

    // ACT
    act(() => {
      result.current.form.setValue('body', '');
    });
    act(() => {
      vi.advanceTimersByTime(500);
    });

    // ASSERT
    expect(sessionStorage.getItem('draft-key-7')).toBeNull();
  });

  it("given the key changes, rehydrates the form with the new key's draft", () => {
    // ARRANGE
    sessionStorage.setItem('draft-key-2-7', JSON.stringify({ body: 'the other draft' }));

    const { result, rerender } = renderDraftFormHook('draft-key-1');

    // ACT
    rerender({ draftKey: 'draft-key-2' });

    // ASSERT
    expect(result.current.form.getValues('body')).toEqual('the other draft');
  });

  it('given the key changes and the new key has no draft, resets the form to its base defaults', () => {
    // ARRANGE
    const { result, rerender } = renderDraftFormHook('draft-key-1');

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
    const { result, rerender } = renderDraftFormHook('draft-key-1');

    act(() => {
      result.current.form.setValue('body', 'hello');
    });

    // ACT
    rerender({ draftKey: 'draft-key-2' });

    // ASSERT
    expect(sessionStorage.getItem('draft-key-1-7')).toEqual(JSON.stringify({ body: 'hello' }));
  });

  it('given the key is unchanged on a rerender, leaves the current values alone', () => {
    // ARRANGE
    const { result, rerender } = renderDraftFormHook('draft-key');

    act(() => {
      result.current.form.setValue('body', 'hello');
    });

    // ACT
    rerender({ draftKey: 'draft-key' });

    // ASSERT
    expect(result.current.form.getValues('body')).toEqual('hello');
  });

  it('given the key becomes null, leaves the current values alone', () => {
    // ARRANGE
    const { result, rerender } = renderDraftFormHook('draft-key');

    act(() => {
      result.current.form.setValue('body', 'hello');
    });

    // ACT
    rerender({ draftKey: null });

    // ASSERT
    expect(result.current.form.getValues('body')).toEqual('hello');
  });

  it('given a field is excluded from the draft, does not restore it', () => {
    // ARRANGE
    sessionStorage.setItem(
      'draft-key-7',
      JSON.stringify({ body: 'restore me', recipient: 'leave me' }),
    );

    // ACT
    const { result } = renderHook(
      () =>
        useDraftForm<{ body: string; recipient: string }>('draft-key', {
          defaultValues: { body: '', recipient: '' },
          excludeFromDraft: ['recipient'],
        }),
      { pageProps: { auth: { user: createAuthenticatedUser({ id: 7 }) } } },
    );

    // ASSERT
    expect(result.current.form.getValues('body')).toEqual('restore me');
    expect(result.current.form.getValues('recipient')).toEqual('');
  });

  it('given the browser refuses to store the draft, keeps the failure contained', () => {
    // ARRANGE
    stubUnavailableStorage();

    const { result } = renderDraftFormHook('draft-key');

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

    const { result, unmount } = renderDraftFormHook('draft-key');

    act(() => {
      result.current.form.setValue('body', 'hello');
    });

    // ACT & ASSERT
    expect(() => unmount()).not.toThrow();
  });

  it('given the browser refuses to remove the draft, keeps the failure contained', () => {
    // ARRANGE
    stubUnavailableStorage();

    const { result } = renderDraftFormHook('draft-key');

    // ACT & ASSERT
    expect(() => {
      act(() => {
        result.current.clearDraft();
      });
    }).not.toThrow();
  });
});
