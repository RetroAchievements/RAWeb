import { useUpdateEffect } from 'react-use';

export function useSyncSystemQueryParam(selectedSystemId?: number | null) {
  useUpdateEffect(() => {
    const searchParams = new URLSearchParams(window.location.search);

    updateSystemId(searchParams, selectedSystemId);

    // `searchParams.size` is not supported in all envs, especially Node.js (Vitest).
    const searchParamsSize = Array.from(searchParams).length;

    const newUrl = searchParamsSize
      ? `${window.location.pathname}?${searchParams.toString()}`
      : window.location.pathname;

    window.history.replaceState(null, '', newUrl);
  }, [selectedSystemId]);
}

function updateSystemId(searchParams: URLSearchParams, selectedSystemId?: number | null): void {
  if (selectedSystemId && selectedSystemId > 0) {
    searchParams.set('system', String(selectedSystemId));
  } else {
    searchParams.delete('system');
  }
}
