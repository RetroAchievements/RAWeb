import { QueryClient } from '@tanstack/react-query';
import { useState } from 'react';

/**
 * @see https://tanstack.com/query/latest/docs/framework/react/guides/ssr
 */

export function useAppQueryClient() {
  const [appQueryClient] = useState(
    () =>
      new QueryClient({
        defaultOptions: {
          queries: {
            // Don't immediately refetch data during
            // the client-side render.
            staleTime: 60 * 1000,
          },
        },
      }),
  );

  return { appQueryClient };
}
