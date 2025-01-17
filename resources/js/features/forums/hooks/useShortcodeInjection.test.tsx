import type { FC, ReactNode } from 'react';
import { FormProvider, useForm } from 'react-hook-form';
import { LuBold } from 'react-icons/lu';

import { renderHook } from '@/test';
import type { TranslatedString } from '@/types/i18next';

import { useShortcodeInjection } from './useShortcodeInjection';

const Wrapper: FC<{ children: ReactNode }> = ({ children }) => {
  const form = useForm();

  return <FormProvider {...form}>{children}</FormProvider>;
};

describe('Hook: useShortcodeInjection', () => {
  it('renders without crashing', () => {
    // ARRANGE
    const { result } = renderHook(
      () =>
        useShortcodeInjection({
          fieldName: 'description',
        }),
      {
        wrapper: Wrapper,
      },
    );

    // ASSERT
    expect(result.current).toBeTruthy();
  });

  it('given the textarea element does not exist in the DOM, returns early without throwing errors', () => {
    // ARRANGE
    const { result } = renderHook(
      () =>
        useShortcodeInjection({
          fieldName: 'description',
        }),
      {
        wrapper: Wrapper,
      },
    );

    // ASSERT
    expect(() =>
      result.current.injectShortcode({
        start: '[b]',
        end: '[/b]',
        icon: LuBold,
        t_label: 'Bold' as TranslatedString,
      }),
    ).not.toThrow();
  });
});
