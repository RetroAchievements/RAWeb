/* eslint-disable @typescript-eslint/no-explicit-any */

import * as InertiajsReactModule from '@inertiajs/react';
import { render as defaultRender } from '@testing-library/react';
import type { ReactNode } from 'react';

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

type DefaultParams = Parameters<typeof defaultRender>;
type RenderUI = DefaultParams[0];
type RenderOptions<TPageProps = Record<string, unknown>> = DefaultParams[1] & {
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
    wrapper = ({ children }: WrapperProps) => <>{children}</>;
  }

  return {
    ...defaultRender(ui, { wrapper, ...options }),
  };
}
