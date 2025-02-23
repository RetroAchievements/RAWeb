import { useHydrateAtoms } from 'jotai/utils';

import {
  persistedAchievementsAtom,
  persistedGamesAtom,
  persistedHubsAtom,
  persistedTicketsAtom,
  persistedUsersAtom,
} from '../state/shortcode.atoms';

export function useHydrateShortcodeDynamicEntities(
  dynamicEntities: App.Community.Data.ShortcodeDynamicEntities,
) {
  useHydrateAtoms([
    [persistedAchievementsAtom, dynamicEntities.achievements],
    [persistedGamesAtom, dynamicEntities.games],
    [persistedHubsAtom, dynamicEntities.hubs],
    [persistedTicketsAtom, dynamicEntities.tickets],
    [persistedUsersAtom, dynamicEntities.users],
    //
  ]);
}
