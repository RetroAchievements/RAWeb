/* eslint-disable @typescript-eslint/no-explicit-any */

import * as InertiajsReactModule from '@inertiajs/react';
import { render as defaultRender } from '@testing-library/react';
import type { ReactNode } from 'react';

import { AppProviders } from '@/common/components/AppProviders';

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
type RenderOptions = DefaultParams[1] & { pageProps?: Record<string, unknown> }; // augment this as necessary

interface WrapperProps {
  children: ReactNode;
}

export function render(ui: RenderUI, { wrapper, pageProps = {}, ...options }: RenderOptions = {}) {
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
