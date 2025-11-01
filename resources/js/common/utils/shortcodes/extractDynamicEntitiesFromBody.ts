import type { DynamicShortcodeEntities } from '@/common/models';

const shortcodePatterns = {
  achievement: /\[ach=([^\]]+)\]/g,
  game: /\[game=([^\]]+)\]/g,
  hub: /\[hub=([^\]]+)\]/g,
  event: /\[event=([^\]]+)\]/g,
  ticket: /\[ticket=([^\]]+)\]/g,
  user: /\[user=([^\]]+)\]/g,
};

export function extractDynamicEntitiesFromBody(input: string): DynamicShortcodeEntities {
  const entities: DynamicShortcodeEntities = {
    achievementIds: [],
    eventIds: [],
    gameIds: [],
    hubIds: [],
    setIds: [],
    ticketIds: [],
    usernames: [],
  };

  // Extract achievement IDs.
  for (const match of input.matchAll(shortcodePatterns.achievement)) {
    const id = parseInt(match[1], 10);
    if (!isNaN(id)) entities.achievementIds.push(id);
  }

  // Extract game IDs and set IDs.
  // Game shortcodes can be [game=123] or [game=123?set=456].
  for (const match of input.matchAll(shortcodePatterns.game)) {
    const captured = match[1];

    // Check if this is a game shortcode with a set parameter.
    const setMatch = captured.match(/^(\d+)(?:\?|\s)set=(\d+)$/i);
    if (setMatch) {
      // Extract set ID only (we'll resolve the backing game on the backend).
      const setId = parseInt(setMatch[2], 10);
      if (!isNaN(setId)) entities.setIds.push(setId);
    } else {
      // Regular game shortcode without set parameter.
      const id = parseInt(captured, 10);
      if (!isNaN(id)) entities.gameIds.push(id);
    }
  }

  // Extract hub IDs.
  for (const match of input.matchAll(shortcodePatterns.hub)) {
    const id = parseInt(match[1], 10);
    if (!isNaN(id)) entities.hubIds.push(id);
  }

  // Extract event IDs.
  for (const match of input.matchAll(shortcodePatterns.event)) {
    const id = parseInt(match[1], 10);
    if (!isNaN(id)) entities.eventIds.push(id);
  }

  // Extract ticket IDs.
  for (const match of input.matchAll(shortcodePatterns.ticket)) {
    const id = parseInt(match[1], 10);
    if (!isNaN(id)) entities.ticketIds.push(id);
  }

  // Extract usernames.
  for (const match of input.matchAll(shortcodePatterns.user)) {
    entities.usernames.push(match[1]);
  }

  return {
    achievementIds: [...new Set(entities.achievementIds)],
    gameIds: [...new Set(entities.gameIds)],
    hubIds: [...new Set(entities.hubIds)],
    eventIds: [...new Set(entities.eventIds)],
    ticketIds: [...new Set(entities.ticketIds)],
    usernames: [...new Set(entities.usernames)],
    setIds: [...new Set(entities.setIds)],
  };
}
