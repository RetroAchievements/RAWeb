import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { LaravelReactI18nProvider } from 'laravel-react-i18n';
import type { FC, ReactNode } from 'react';

import { BaseToaster } from '../+vendor/BaseToaster';
import { BaseTooltipProvider } from '../+vendor/BaseTooltip';

const queryClient = new QueryClient();

interface AppProvidersProps {
  children: ReactNode;
}

export const AppProviders: FC<AppProvidersProps> = ({ children }) => {
  return (
    <LaravelReactI18nProvider
      locale="en_US"
      fallbackLocale="en_US"
      files={import.meta.glob('/lang/*.json')}
    >
      <QueryClientProvider client={queryClient}>
        <BaseTooltipProvider delayDuration={300}>
          {children}

          <BaseToaster richColors={true} duration={4000} />
        </BaseTooltipProvider>
      </QueryClientProvider>
    </LaravelReactI18nProvider>
  );
};
