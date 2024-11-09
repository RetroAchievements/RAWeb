/* eslint-disable no-restricted-imports -- test setup can import from @testing-library/react */
/* eslint-disable @typescript-eslint/no-explicit-any */

import * as InertiajsReactModule from '@inertiajs/react';
import type { RenderHookOptions as RTLRenderHookOptions } from '@testing-library/react';
import { render as defaultRender, renderHook as defaultRenderHook } from '@testing-library/react';
import type { ReactNode } from 'react';

import { AppProviders } from '@/common/components/AppProviders';
import type { AppGlobalProps } from '@/common/models';
import i18n from '@/i18n-client';

export * from '@testing-library/react';

vi.mock('@inertiajs/react', async (importOriginal) => {
  const original = (await importOriginal()) as any;

  return {
    ...original,
    __esModule: true,
    usePage: vi.fn(),
  };
});

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

type DefaultRenderParams = Parameters<typeof defaultRender>;
type RenderUI = DefaultRenderParams[0];
type RenderOptions<TPageProps = Record<string, unknown>> = DefaultRenderParams[1] & {
  pageProps?: Partial<TPageProps & AppGlobalProps>;
};

interface WrapperProps {
  children: ReactNode;
}

export function render<TPageProps = Record<string, unknown>>(
  ui: RenderUI,
  {
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
  }));

  if (!wrapper) {
    wrapper = ({ children }: WrapperProps) => <AppProviders i18n={i18n}>{children}</AppProviders>;
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
  pageProps?: Partial<AppGlobalProps>;
};

export function renderHook<Result, Props = undefined>(
  callback: (props: Props) => Result,
  {
    wrapper,
    initialProps,
    pageProps = {} as Partial<AppGlobalProps>,
    ...options
  }: RenderHookOptions<Props> = {},
) {
  vi.spyOn(InertiajsReactModule, 'usePage').mockImplementation(() => ({
    component: '',
    props: pageProps as any,
    rememberedState: {},
    scrollRegions: vi.fn() as any,
    url: '',
    version: '',
  }));

  if (!wrapper) {
    wrapper = ({ children }: WrapperProps) => <AppProviders i18n={i18n}>{children}</AppProviders>;
  }

  return defaultRenderHook(callback, { wrapper, initialProps, ...options });
}
