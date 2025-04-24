import { useSetAtom } from 'jotai';
import { useHydrateAtoms } from 'jotai/utils';
import { useEffect } from 'react';

import {
  persistedAchievementsAtom,
  persistedGamesAtom,
  persistedHubsAtom,
  persistedTicketsAtom,
  persistedUsersAtom,
} from '../state/shortcode.atoms';
import { usePageProps } from './usePageProps';

export function useHydrateShortcodeDynamicEntities(
  initialDynamicEntities: App.Community.Data.ShortcodeDynamicEntities,
) {
  const { dynamicEntities: propsDynamicEntities } = usePageProps<{
    dynamicEntities: App.Community.Data.ShortcodeDynamicEntities;
  }>();

  // Always call useHydrateAtoms during SSR.
  useHydrateAtoms([
    [persistedAchievementsAtom, initialDynamicEntities.achievements],
    [persistedGamesAtom, initialDynamicEntities.games],
    [persistedHubsAtom, initialDynamicEntities.hubs],
    [persistedTicketsAtom, initialDynamicEntities.tickets],
    [persistedUsersAtom, initialDynamicEntities.users],
  ]);

  // These setters are only used for client-side updates.
  const setPersistedAchievements = useSetAtom(persistedAchievementsAtom);
  const setPersistedGames = useSetAtom(persistedGamesAtom);
  const setPersistedHubs = useSetAtom(persistedHubsAtom);
  const setPersistedTickets = useSetAtom(persistedTicketsAtom);
  const setPersistedUsers = useSetAtom(persistedUsersAtom);

  useEffect(() => {
    setPersistedAchievements(propsDynamicEntities.achievements);
    setPersistedGames(propsDynamicEntities.games);
    setPersistedHubs(propsDynamicEntities.hubs);
    setPersistedTickets(propsDynamicEntities.tickets);
    setPersistedUsers(propsDynamicEntities.users);
  }, [
    propsDynamicEntities,
    setPersistedAchievements,
    setPersistedGames,
    setPersistedHubs,
    setPersistedTickets,
    setPersistedUsers,
  ]);
}
