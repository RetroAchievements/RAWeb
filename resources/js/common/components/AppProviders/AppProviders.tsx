import { QueryClientProvider } from '@tanstack/react-query';
import { Provider as JotaiProvider } from 'jotai';
import { domAnimation, LazyMotion } from 'motion/react';
import { type FC, lazy, type ReactNode, Suspense } from 'react';
import { I18nextProvider, type I18nextProviderProps } from 'react-i18next';

import { BaseToaster } from '../+vendor/BaseToaster';
import { BaseTooltipProvider } from '../+vendor/BaseTooltip';
import { GlobalSearchProvider } from '../GlobalSearchProvider';
import { useAppQueryClient } from './useAppQueryClient';

const ReactQueryDevtools = lazy(() =>
  import('@tanstack/react-query-devtools').then((m) => ({ default: m.ReactQueryDevtools })),
);

interface AppProvidersProps {
  children: ReactNode;
  i18n: I18nextProviderProps['i18n'];
}

export const AppProviders: FC<AppProvidersProps> = ({ children, i18n }) => {
  const { appQueryClient } = useAppQueryClient();

  return (
    <QueryClientProvider client={appQueryClient}>
      <I18nextProvider i18n={i18n}>
        <JotaiProvider>
          <LazyMotion features={domAnimation}>
            <BaseTooltipProvider delayDuration={300}>
              <GlobalSearchProvider>
                {children}

                <BaseToaster richColors={true} duration={4000} />

                {/* Everything below this line is excluded from prod builds. */}
                {import.meta.env.VITE_REACT_QUERY_DEVTOOLS_ENABLED === 'true' ? (
                  <Suspense>
                    <div className="hidden text-lg sm:block" data-testid="query-devtools">
                      <ReactQueryDevtools />
                    </div>
                  </Suspense>
                ) : null}
              </GlobalSearchProvider>
            </BaseTooltipProvider>
          </LazyMotion>
        </JotaiProvider>
      </I18nextProvider>
    </QueryClientProvider>
  );
};
