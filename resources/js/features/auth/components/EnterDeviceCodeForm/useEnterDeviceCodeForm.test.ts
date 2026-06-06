import { router } from '@inertiajs/react';

import { act, renderHook } from '@/test';

import { useEnterDeviceCodeForm } from './useEnterDeviceCodeForm';

describe('Hook: useEnterDeviceCodeForm', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { result } = renderHook(() => useEnterDeviceCodeForm());

    // ASSERT
    expect(result.current).toBeTruthy();
  });

  it('given onSubmit is called, sets isNavigating to true and calls router.visit', () => {
    // ARRANGE
    const { result } = renderHook(() => useEnterDeviceCodeForm());

    // ACT
    act(() => {
      result.current.onSubmit({ userCode: 'ABCD-EFGH' });
    });

    // ASSERT
    expect(result.current.isNavigating).toEqual(true);
    expect(router.visit).toHaveBeenCalledOnce();
  });

  it('given onError receives an empty errors object, does not set a form error', () => {
    // ARRANGE
    const { result } = renderHook(() => useEnterDeviceCodeForm());

    act(() => {
      result.current.onSubmit({ userCode: 'ABCD-EFGH' });
    });

    const visitCall = vi.mocked(router.visit).mock.calls[0];
    const options = visitCall[1] as { onError: (errors: Record<string, string>) => void };

    // ACT
    act(() => {
      options.onError({});
    });

    // ASSERT
    expect(result.current.form.formState.errors.userCode).toBeUndefined();
  });

  it('given onFinish is called, sets isNavigating back to false', () => {
    // ARRANGE
    const { result } = renderHook(() => useEnterDeviceCodeForm());

    act(() => {
      result.current.onSubmit({ userCode: 'ABCD-EFGH' });
    });

    expect(result.current.isNavigating).toEqual(true);

    const visitCall = vi.mocked(router.visit).mock.calls[0];
    const options = visitCall[1] as { onFinish: () => void };

    // ACT
    act(() => {
      options.onFinish();
    });

    // ASSERT
    expect(result.current.isNavigating).toEqual(false);
  });
});
