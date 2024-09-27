/* eslint-disable @typescript-eslint/no-explicit-any */

import * as InertiajsReactModule from '@inertiajs/react';
import { render as defaultRender } from '@testing-library/react';
import type { ReactNode } from 'react';

import { AppProviders } from '@/common/components/AppProviders';
import type { AppGlobalProps } from '@/common/models';

export * from '@testing-library/react';

vi.mock('@inertiajs/react', () => ({
  __esModule: true,
  usePage: vi.fn(),
}));

/**
 * laravel-react-i18n does not support Vitest, and without any intervention
 * it will believe it's being rendered in the browser and cause React to
 * output no DOM. Rather than trying to hack laravel-react-i18n to fix this,
 * it's better to just mock the functions we need to test basic UI functionality.
 */
vi.mock('@/lib/laravel-react-i18n', () => ({
  __esModule: true,

  LaravelReactI18nProvider: ({ children }: any) => <>{children}</>,

  useLaravelReactI18n: () => ({
    loading: false,
    currentLocale: () => 'en_US',

    /**
     * t('Welcome!'); // "Welcome"
     * t('Welcome, :name!', { name: 'Francisco' }); // "Welcome, Francisco!"
     * t(':count apples', { count: 2 }); // "2 apples"
     */
    t: (key: string, replacements: Record<string, any>) => {
      return key.replace(/:([a-zA-Z]+)/g, (_, match) => {
        const replacementKey = Object.keys(replacements).find(
          (key) => key.toLowerCase() === match.toLowerCase(),
        );

        const replacement = replacementKey ? replacements[replacementKey] : match;

        return replacement !== undefined ? String(replacement) : match;
      });
    },

    /**
     * tChoice('{0} There are none|[1,19] There are some|[20,*] There are many', 0); // "There are none"
     * tChoice('{0} There are none|[1,19] There are some|[20,*] There are many', 4); // "There are some"
     * tChoice('{0} There are none|[1,19] There are some|[20,*] There are many', 20); // "There are many"
     * tChoice('There is one apple|There are many apples', 1); // "There is one apple"
     * tChoice('There is one apple|There are many apples', 4); // "There are many apples"
     * tChoice('{1} :count minute ago|[2,*] :count minutes ago', 1); // "1 minute ago"
     * tChoice('{1} :count minute ago|[2,*] :count minutes ago', 4); // "4 minutes ago"
     */
    tChoice: (key: string, count: number, replacements: Record<string, any> = {}) => {
      const choices = key.split('|');
      let selectedChoice = choices[choices.length - 1]; // Default to the last choice (plural)

      for (let i = 0; i < choices.length; i++) {
        const match = choices[i].match(/^\{(\d+)\}\s|^\[(\d+),(\*|\d+)\]\s/);
        if (match) {
          const exact = match[1] !== undefined ? parseInt(match[1]) : undefined;
          const rangeStart = match[2] !== undefined ? parseInt(match[2]) : undefined;
          const rangeEnd =
            match[3] === '*' ? Infinity : match[3] !== undefined ? parseInt(match[3]) : undefined;

          if (exact !== undefined && count === exact) {
            selectedChoice = choices[i].replace(match[0], '').trim();
            break;
          } else if (
            rangeEnd &&
            rangeStart !== undefined &&
            count >= rangeStart &&
            count <= rangeEnd
          ) {
            selectedChoice = choices[i].replace(match[0], '').trim();
            break;
          }
        } else if (count === 1 && choices.length === 2) {
          selectedChoice = choices[0].trim();
          break;
        }
      }

      return selectedChoice.replace(/:([a-zA-Z]+)/g, (_, match) => {
        const replacement = replacements[match] || count;

        return replacement;
      });
    },
  }),
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
    wrapper = ({ children }: WrapperProps) => <AppProviders>{children}</AppProviders>;
  }

  return {
    ...defaultRender(ui, { wrapper, ...options }),
  };
}
