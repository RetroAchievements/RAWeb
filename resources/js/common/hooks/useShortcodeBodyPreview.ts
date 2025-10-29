import { useAtom } from 'jotai';
import { useState } from 'react';

import {
  persistedAchievementsAtom,
  persistedEventsAtom,
  persistedGamesAtom,
  persistedHubsAtom,
  persistedTicketsAtom,
  persistedUsersAtom,
} from '@/common/state/shortcode.atoms';
import { extractDynamicEntitiesFromBody } from '@/common/utils/shortcodes/extractDynamicEntitiesFromBody';
import { preProcessShortcodesInBody } from '@/common/utils/shortcodes/preProcessShortcodesInBody';

import type { ShortcodeBodyPreviewMutationResponse } from '../models';
import { useShortcodeBodyPreviewMutation } from './mutations/useShortcodeBodyPreviewMutation';

export function useShortcodeBodyPreview() {
  const [previewContent, setPreviewContent] = useState<string | null>(null);

  const [persistedAchievements, setPersistedAchievements] = useAtom(persistedAchievementsAtom);
  const [persistedGames, setPersistedGames] = useAtom(persistedGamesAtom);
  const [persistedHubs, setPersistedHubs] = useAtom(persistedHubsAtom);
  const [persistedEvents, setPersistedEvents] = useAtom(persistedEventsAtom);
  const [persistedTickets, setPersistedTickets] = useAtom(persistedTicketsAtom);
  const [persistedUsers, setPersistedUsers] = useAtom(persistedUsersAtom);

  const mutation = useShortcodeBodyPreviewMutation();

  const mergeRetrievedEntities = (responseData: ShortcodeBodyPreviewMutationResponse) => {
    setPersistedAchievements((prev) =>
      mergeEntities(prev, responseData.achievements, (item) => item.id),
    );
    setPersistedGames((prev) => mergeEntities(prev, responseData.games, (item) => item.id));
    setPersistedHubs((prev) => mergeEntities(prev, responseData.hubs, (item) => item.id));
    setPersistedEvents((prev) => mergeEntities(prev, responseData.events, (item) => item.id));
    setPersistedTickets((prev) => mergeEntities(prev, responseData.tickets, (item) => item.id));
    setPersistedUsers((prev) =>
      mergeEntities(prev, responseData.users, (item) => item.displayName),
    );
  };

  const initiatePreview = async (body: string) => {
    // Normalize any internal URLs to shortcode format.
    let normalizedBody = preProcessShortcodesInBody(body);

    // Then, extract dynamic entities from the normalized content.
    const dynamicEntities = extractDynamicEntitiesFromBody(normalizedBody);

    // Do we have any dynamic entities to fetch from the server?
    // If not, we'll skip a round trip to the server to make the preview seem instantaneous.
    const hasDynamicEntities = Object.values(dynamicEntities).some((arr) => arr.length > 0);

    // If there are no dynamic entities in the post content, skip the round trip to the server.
    if (hasDynamicEntities) {
      const response = await mutation.mutateAsync(dynamicEntities);
      mergeRetrievedEntities(response.data);

      // Replace [game=X?set=Y] shortcodes with their backing game IDs.
      normalizedBody = replaceGameSetShortcodesWithBackingGames(
        normalizedBody,
        dynamicEntities,
        response.data.games,
      );
    }

    setPreviewContent(normalizedBody);
  };

  /**
   * Intended for testing. In components, reach for `useAtom()`.
   */
  const unsafe_getPersistedValues = () => {
    return {
      persistedAchievements,
      persistedGames,
      persistedHubs,
      persistedEvents,
      persistedTickets,
      persistedUsers,
    };
  };

  return { initiatePreview, unsafe_getPersistedValues, previewContent };
}

function mergeEntities<TEntity>(
  existing: TEntity[] | null | undefined,
  newItems: TEntity[],
  getKey: (item: TEntity) => string | number,
): TEntity[] {
  const existingMap = new Map(existing?.map((item) => [getKey(item), item]) ?? []);

  for (const item of newItems) {
    if (!existingMap.has(getKey(item))) {
      existingMap.set(getKey(item), item);
    }
  }

  return Array.from(existingMap.values());
}

function replaceGameSetShortcodesWithBackingGames(
  body: string,
  dynamicEntities: ReturnType<typeof extractDynamicEntitiesFromBody>,
  games: ShortcodeBodyPreviewMutationResponse['games'],
): string {
  if (dynamicEntities.setIds.length === 0 || games.length === 0) {
    return body;
  }

  // Count how many games came from regular gameIds vs setIds.
  const gamesFromRegularIds = dynamicEntities.gameIds.length;
  const gamesFromSets = games.slice(gamesFromRegularIds);

  // Build a map of setId -> backing game ID.
  const setToBackingGameMap = new Map<number, number>();
  for (const [index, setId] of dynamicEntities.setIds.entries()) {
    if (gamesFromSets[index]) {
      setToBackingGameMap.set(setId, gamesFromSets[index].id);
    }
  }

  // Replace each [game=X?set=Y] with [game=backingGameId].
  if (setToBackingGameMap.size === 0) {
    return body;
  }

  return body.replace(/\[game=\d+(?:\?|\s)set=(\d+)\]/gi, (match, setIdStr) => {
    const setId = parseInt(setIdStr, 10);
    const backingGameId = setToBackingGameMap.get(setId);

    return backingGameId ? `[game=${backingGameId}]` : match;
  });
}
