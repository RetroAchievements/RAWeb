import { useUpdateEffect } from 'react-use';

// TODO user's persistence cookie support

/**
 * This hook is designed to keep the URL query params and
 * user's persistence cookie in sync with the table state.
 */
export function useTicketListTableSync(pageNumber: number) {
  useUpdateEffect(() => {
    const searchParams = new URLSearchParams(window.location.search);

    if (pageNumber > 1) {
      searchParams.set('page[number]', String(pageNumber));
    } else {
      searchParams.delete('page[number]');
    }

    const newUrl = Array.from(searchParams).length
      ? `${window.location.pathname}?${searchParams.toString()}`
      : window.location.pathname;

    const currentUrl = `${window.location.pathname}${window.location.search}`;

    if (newUrl === currentUrl) {
      return;
    }

    window.history.pushState({ inertia: true }, '', newUrl);
  }, [pageNumber]);
}
