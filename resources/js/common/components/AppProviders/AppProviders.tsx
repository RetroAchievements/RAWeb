import { QueryClientProvider } from '@tanstack/react-query';
import { ReactQueryDevtools } from '@tanstack/react-query-devtools';
import { type FC, type ReactNode } from 'react';

import { BaseToaster } from '../+vendor/BaseToaster';
import { BaseTooltipProvider } from '../+vendor/BaseTooltip';
import { useAppQueryClient } from './useAppQueryClient';

interface AppProvidersProps {
  children: ReactNode;
}

export const AppProviders: FC<AppProvidersProps> = ({ children }) => {
  const { appQueryClient } = useAppQueryClient();

  return (
    <QueryClientProvider client={appQueryClient}>
      <BaseTooltipProvider delayDuration={300}>
        {children}

        <BaseToaster richColors={true} duration={4000} />

        {/* Everything below this line is excluded from prod builds. */}
        {import.meta.env.VITE_REACT_QUERY_DEVTOOLS_ENABLED === 'true' ? (
          <div className="text-lg">
            <ReactQueryDevtools />
          </div>
        ) : null}
      </BaseTooltipProvider>
    </QueryClientProvider>
  );
};
