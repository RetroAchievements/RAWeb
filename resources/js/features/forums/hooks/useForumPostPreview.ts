import { useAtom } from 'jotai';
import { useState } from 'react';

import {
  persistedAchievementsAtom,
  persistedGamesAtom,
  persistedTicketsAtom,
  persistedUsersAtom,
} from '../state/forum.atoms';
import { extractDynamicEntitiesFromBody } from '../utils/extractDynamicEntitiesFromBody';
import { preProcessShortcodesInBody } from '../utils/preProcessShortcodesInBody';
import type { ForumPostPreviewMutationResponse } from './useForumPostPreviewMutation';
import { useForumPostPreviewMutation } from './useForumPostPreviewMutation';

export function useForumPostPreview() {
  const [previewContent, setPreviewContent] = useState<string | null>(null);

  const [persistedAchievements, setPersistedAchievements] = useAtom(persistedAchievementsAtom);
  const [persistedGames, setPersistedGames] = useAtom(persistedGamesAtom);
  const [persistedTickets, setPersistedTickets] = useAtom(persistedTicketsAtom);
  const [persistedUsers, setPersistedUsers] = useAtom(persistedUsersAtom);

  const mutation = useForumPostPreviewMutation();

  const mergeRetrievedEntities = (responseData: ForumPostPreviewMutationResponse) => {
    setPersistedAchievements((prev) =>
      mergeEntities(prev, responseData.achievements, (item) => item.id),
    );
    setPersistedGames((prev) => mergeEntities(prev, responseData.games, (item) => item.id));
    setPersistedTickets((prev) => mergeEntities(prev, responseData.tickets, (item) => item.id));
    setPersistedUsers((prev) =>
      mergeEntities(prev, responseData.users, (item) => item.displayName),
    );
  };

  const initiatePreview = async (body: string) => {
    // Normalize any internal URLs to shortcode format.
    const normalizedBody = preProcessShortcodesInBody(body);

    // Then, extract dynamic entities from the normalized content.
    const dynamicEntities = extractDynamicEntitiesFromBody(normalizedBody);

    // Do we have any dynamic entities to fetch from the server?
    // If not, we'll skip a round trip to the server to make the preview seem instantaneous.
    const hasDynamicEntities = Object.values(dynamicEntities).some((arr) => arr.length > 0);

    // If there are no dynamic entities in the post content, skip the round trip to the server.
    if (hasDynamicEntities) {
      const response = await mutation.mutateAsync(dynamicEntities);
      mergeRetrievedEntities(response.data);
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
