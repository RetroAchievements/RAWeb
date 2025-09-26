import { useEffect, useState } from 'react';
import { useCookie } from 'react-use';
import { route } from 'ziggy-js';

/**
 * Users can persist their datatable preferences, which are stored in a cookie.
 * If the user has hub datatable preferences, we don't want to tack on query
 * params to this URL, as those params override whatever prefs the user has set.
 */
export function useSeriesHubHref(gameSetId: number) {
  const [rawViewPreferences] = useCookie('datatable_view_preference_hub_games');

  const [href, setHref] = useState(() =>
    // Start with default params for SSR and the initial render.
    route('hub.show', {
      gameSet: gameSetId,
      sort: '-playersTotal',
      'filter[subsets]': 'only-games',
    }),
  );

  useEffect(() => {
    // Check for persisted preferences during the initial client-side render.
    if (rawViewPreferences) {
      // User has preferences - remove the default params.
      setHref(route('hub.show', { gameSet: gameSetId }));
    }
    // If no preferences, keep the default params.
    // eslint-disable-next-line react-hooks/exhaustive-deps -- intentional
  }, [gameSetId]);

  return { href };
}
