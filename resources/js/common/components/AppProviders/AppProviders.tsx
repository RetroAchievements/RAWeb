import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import type { FC, ReactNode } from 'react';

import { BaseToaster } from '../+vendor/BaseToaster';
import { BaseTooltipProvider } from '../+vendor/BaseTooltip';

const queryClient = new QueryClient();

interface AppProvidersProps {
  children: ReactNode;
}

export const AppProviders: FC<AppProvidersProps> = ({ children }) => {
  return (
    <QueryClientProvider client={queryClient}>
      <BaseTooltipProvider delayDuration={300}>
        {children}

        <BaseToaster richColors={true} duration={4000} />
      </BaseTooltipProvider>
    </QueryClientProvider>
  );
};
