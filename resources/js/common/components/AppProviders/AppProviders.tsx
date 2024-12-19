import { QueryClientProvider } from '@tanstack/react-query';
import { ReactQueryDevtools } from '@tanstack/react-query-devtools';
import { Provider as JotaiProvider } from 'jotai';
import { domAnimation, LazyMotion } from 'motion/react';
import { type FC, type ReactNode } from 'react';
import { I18nextProvider, type I18nextProviderProps } from 'react-i18next';

import { BaseToaster } from '../+vendor/BaseToaster';
import { BaseTooltipProvider } from '../+vendor/BaseTooltip';
import { useAppQueryClient } from './useAppQueryClient';

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
              {children}

              <BaseToaster richColors={true} duration={4000} />

              {/* Everything below this line is excluded from prod builds. */}
              {import.meta.env.VITE_REACT_QUERY_DEVTOOLS_ENABLED === 'true' ? (
                <div className="hidden text-lg sm:block" data-testid="query-devtools">
                  <ReactQueryDevtools />
                </div>
              ) : null}
            </BaseTooltipProvider>
          </LazyMotion>
        </JotaiProvider>
      </I18nextProvider>
    </QueryClientProvider>
  );
};
