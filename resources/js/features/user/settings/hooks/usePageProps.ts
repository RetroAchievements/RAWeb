import { usePage } from '@inertiajs/react';

import type { AppGlobalProps } from '@/common/models';

export function usePageProps<TPageProps = unknown>() {
  const { props } = usePage<AppGlobalProps & TPageProps>();

  return props;
}
