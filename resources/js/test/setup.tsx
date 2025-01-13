/* eslint-disable no-restricted-imports -- test setup can import from @testing-library/react */
/* eslint-disable @typescript-eslint/no-explicit-any */

import * as InertiajsReactModule from '@inertiajs/react';
import type { RenderHookOptions as RTLRenderHookOptions } from '@testing-library/react';
import { render as defaultRender, renderHook as defaultRenderHook } from '@testing-library/react';
import type { WritableAtom } from 'jotai';
import type { useHydrateAtoms } from 'jotai/utils';
import type { ReactNode } from 'react';

import { AppProviders } from '@/common/components/AppProviders';
import type { AppGlobalProps } from '@/common/models';
import i18n from '@/i18n-client';

import { HydrateAtoms } from './HydrateAtoms';

export * from '@testing-library/react';

vi.mock('@inertiajs/react', async (importOriginal) => {
  const original = (await importOriginal()) as any;

  return {
    ...original,
    __esModule: true,

    Head: ({ children }: { children: ReactNode }) => (
      <div data-testid="head-content">{children}</div>
    ),

    usePage: vi.fn(),
  };
});

/**
 * Before reaching for this util, make absolutely sure your problem can't
 * be solved by using `waitFor()` or `screen.findBy*()`. This should
 * only be used as a last resort!
 */
export function __UNSAFE_VERY_DANGEROUS_SLEEP(milliseconds: number) {
  return new Promise((resolve) => {
    setTimeout(resolve, milliseconds);
  });
}

/*
|--------------------------------------------------------------------------
| Test Suite Custom Render Method
|--------------------------------------------------------------------------
|
| It's often useful to define a custom render method that includes things
| like global context providers, data stores, etc.
| https://testing-library.com/docs/react-testing-library/setup#custom-render
|
*/

type UseHydrateAtomsValues = Parameters<typeof useHydrateAtoms>[0];

// Convert simple tuples to readonly tuples that Jotai is happy with.
function toHydrateValues(atoms: RenderOptions['jotaiAtoms'] = []): UseHydrateAtomsValues {
  return atoms as unknown as UseHydrateAtomsValues;
}

type DefaultRenderParams = Parameters<typeof defaultRender>;
type RenderUI = DefaultRenderParams[0];
type RenderOptions<TPageProps = Record<string, unknown>> = DefaultRenderParams[1] & {
  jotaiAtoms?: [WritableAtom<unknown, any[], unknown>, unknown][];
  pageProps?: Partial<TPageProps & AppGlobalProps>;
};

interface WrapperProps {
  children: ReactNode;
}

export function render<TPageProps = Record<string, unknown>>(
  ui: RenderUI,
  {
    jotaiAtoms,
    wrapper,
    pageProps = {} as Partial<TPageProps & AppGlobalProps>,
    ...options
  }: RenderOptions<TPageProps> = {},
) {
  vi.spyOn(InertiajsReactModule, 'usePage').mockImplementation(() => ({
    component: '',
    props: pageProps as any,
    rememberedState: {},
    scrollRegions: vi.fn() as any,
    url: '',
    version: '',
    clearHistory: false,
    encryptHistory: false,
  }));

  if (!wrapper) {
    wrapper = ({ children }: WrapperProps) => (
      <AppProviders i18n={i18n}>
        <HydrateAtoms initialValues={toHydrateValues(jotaiAtoms)}>{children}</HydrateAtoms>
      </AppProviders>
    );
  }

  return {
    ...defaultRender(ui, { wrapper, ...options }),
  };
}

/*
|--------------------------------------------------------------------------
| Test Suite Custom Render Hook Method
|--------------------------------------------------------------------------
|
| It's often useful to define a custom renderHook method that includes things
| like global context providers, data stores, etc.
| https://testing-library.com/docs/react-testing-library/setup#custom-render
|
*/

type RenderHookOptions<Props> = RTLRenderHookOptions<Props> & {
  jotaiAtoms?: [WritableAtom<unknown, any[], unknown>, unknown][];
  pageProps?: Partial<AppGlobalProps>;
  url?: any;
};

export function renderHook<Result, Props = undefined>(
  callback: (props: Props) => Result,
  {
    initialProps,
    jotaiAtoms,
    url,
    wrapper,
    pageProps = {} as Partial<AppGlobalProps>,
    ...options
  }: RenderHookOptions<Props> = {},
) {
  vi.spyOn(InertiajsReactModule, 'usePage').mockImplementation(() => ({
    component: '',
    props: pageProps as any,
    rememberedState: {},
    scrollRegions: vi.fn() as any,
    url: url ?? '',
    version: '',
    clearHistory: false,
    encryptHistory: false,
  }));

  if (!wrapper) {
    wrapper = ({ children }: WrapperProps) => (
      <AppProviders i18n={i18n}>
        <HydrateAtoms initialValues={toHydrateValues(jotaiAtoms)}>{children}</HydrateAtoms>
      </AppProviders>
    );
  }

  return defaultRenderHook(callback, { wrapper, initialProps, ...options });
}
