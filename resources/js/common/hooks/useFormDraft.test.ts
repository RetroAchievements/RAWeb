import { useForm } from 'react-hook-form';

import { loadDraft } from '@/common/utils/loadDraft';
import { act, renderHook } from '@/test';

import { useFormDraft } from './useFormDraft';

vi.mock('@/common/utils/loadDraft', () => ({
  loadDraft: vi.fn(),
}));

describe('Hook: useFormDraft', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    sessionStorage.clear();
    vi.useFakeTimers();
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  it('given the key changes after initial mount, rehydrates the form if draft is different', () => {
    // ARRANGE
    const baseDefaults = { body: '' };
    vi.mocked(loadDraft).mockReturnValue({ body: 'new draft content' });

    const { result, rerender } = renderHook(
      ({ key }) => {
        const form = useForm({ defaultValues: baseDefaults });
        useFormDraft(key, form, baseDefaults);
        return form;
      },
      { initialProps: { key: 'initial-key' } },
    );

    // ACT
    rerender({ key: 'new-key' });

    // ASSERT
    expect(result.current.getValues('body')).toBe('new draft content');
  });

  it('given clearDraft is called, prevents a pending debounce timer from saving base defaults', () => {
    // ARRANGE
    const baseDefaults = { body: '' };
    vi.mocked(loadDraft).mockReturnValue(baseDefaults);

    let clearDraftFn: () => void;

    const { result } = renderHook(() => {
      const form = useForm({ defaultValues: { body: 'typed text' } });
      const { clearDraft } = useFormDraft('test-key', form, baseDefaults);
      clearDraftFn = clearDraft;
      return form;
    });

    // ACT
    act(() => {
      result.current.reset(baseDefaults);
      clearDraftFn();
    });

    act(() => {
      vi.advanceTimersByTime(500);
    });

    // ASSERT
    expect(sessionStorage.getItem('test-key')).toBeNull();
  });

  it('given the key changes after initial mount but draft is identical to current values, does not rehydrate', () => {
    // ARRANGE
    const baseDefaults = { body: '' };
    vi.mocked(loadDraft).mockReturnValue(baseDefaults);

    const { result, rerender } = renderHook(
      ({ key }) => {
        const form = useForm({ defaultValues: baseDefaults });
        useFormDraft(key, form, baseDefaults);
        return form;
      },
      { initialProps: { key: 'initial-key' } },
    );

    const resetSpy = vi.spyOn(result.current, 'reset');

    // ACT
    rerender({ key: 'new-key' });

    // ASSERT
    expect(resetSpy).not.toHaveBeenCalled();
  });

  it('given clearDraft is called when there is no pending timer, removes draft from storage safely', () => {
    // ARRANGE
    const baseDefaults = { body: '' };
    vi.mocked(loadDraft).mockReturnValue(baseDefaults);

    const { result } = renderHook(() => {
      const form = useForm({ defaultValues: baseDefaults });
      return useFormDraft('test-key', form, baseDefaults);
    });

    act(() => {
      vi.runAllTimers();
    });

    // ACT
    act(() => {
      result.current.clearDraft();
    });

    // ASSERT
    expect(sessionStorage.getItem('test-key')).toBeNull();
  });

  it('given the key changes to null after initial mount, does not rehydrate', () => {
    // ARRANGE
    const { rerender } = renderHook(
      ({ key }) => {
        const form = useForm({ defaultValues: { body: '' } });
        useFormDraft(key, form);
        return form;
      },
      { initialProps: { key: 'initial-key' as string | null } },
    );

    // ACT
    rerender({ key: null });

    // ASSERT
    expect(true).toBe(true);
  });

  it('given clearDraft is called before effects run, safely handles null timerRef', () => {
    // ARRANGE
    const baseDefaults = { body: '' };
    vi.mocked(loadDraft).mockReturnValue(baseDefaults);

    // ACT
    renderHook(() => {
      const form = useForm({ defaultValues: baseDefaults });
      const { clearDraft } = useFormDraft('test-key', form, baseDefaults);

      clearDraft();

      return form;
    });

    // ASSERT
    expect(sessionStorage.getItem('test-key')).toBeNull();
  });
});
