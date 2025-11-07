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
    const normalizedBody = preProcessShortcodesInBody(body);

    // Send the body to the server for entity extraction and fetching.
    // The server will handle normalizing, converting [game=X?set=Y] to backing game IDs, and extracting entities.
    const response = await mutation.mutateAsync(normalizedBody);
    mergeRetrievedEntities(response.data);

    // Use the converted body from the server (eg: "[game=X?set=Y]"" has been converted to "[game=backingGameId]").
    setPreviewContent(response.data.convertedBody);
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
