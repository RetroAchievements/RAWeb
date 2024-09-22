/* eslint-disable @typescript-eslint/no-explicit-any */

import * as InertiajsReactModule from '@inertiajs/react';
import { render as defaultRender, renderHook as defaultRenderHook } from '@testing-library/react';
import type { ReactNode } from 'react';

import { AppProviders } from '@/common/components/AppProviders';
import type { AppGlobalProps } from '@/common/models';

export * from '@testing-library/react';

vi.mock('@inertiajs/react', () => ({
  __esModule: true,
  usePage: vi.fn(),
}));

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
    wrapper = ({ children }: WrapperProps) => <AppProviders>{children}</AppProviders>;
  }

  return {
    ...defaultRender(ui, { wrapper, ...options }),
  };
}

// -----------------

type DefaultRenderHookParams = Parameters<typeof defaultRenderHook>;
type RenderCallback = DefaultRenderHookParams[0];
type RenderHookOptions<TPageProps = Record<string, unknown>> = DefaultRenderHookParams[1] & {
  pageProps?: Partial<TPageProps & AppGlobalProps>;
};

export function renderHook<TPageProps = Record<string, unknown>>(
  callback: RenderCallback,
  {
    wrapper,
    pageProps = {} as Partial<TPageProps & AppGlobalProps>,
    ...options
  }: RenderHookOptions<TPageProps> = {},
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
    wrapper = ({ children }: WrapperProps) => <AppProviders>{children}</AppProviders>;
  }

  return {
    ...defaultRenderHook(callback, { wrapper, ...options }),
  };
}
