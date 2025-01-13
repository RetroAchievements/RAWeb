import type { DynamicShortcodeEntities } from '../models';

const shortcodePatterns = {
  user: /\[user=([^\]]+)\]/g,
  game: /\[game=([^\]]+)\]/g,
  achievement: /\[ach=([^\]]+)\]/g,
  ticket: /\[ticket=([^\]]+)\]/g,
};

export function extractDynamicEntitiesFromBody(input: string): DynamicShortcodeEntities {
  const entities: DynamicShortcodeEntities = {
    usernames: [],
    ticketIds: [],
    achievementIds: [],
    gameIds: [],
  };

  // Extract usernames.
  for (const match of input.matchAll(shortcodePatterns.user)) {
    entities.usernames.push(match[1]);
  }

  // Extract ticket IDs.
  for (const match of input.matchAll(shortcodePatterns.ticket)) {
    const id = parseInt(match[1], 10);
    if (!isNaN(id)) entities.ticketIds.push(id);
  }

  // Extract achievement IDs.
  for (const match of input.matchAll(shortcodePatterns.achievement)) {
    const id = parseInt(match[1], 10);
    if (!isNaN(id)) entities.achievementIds.push(id);
  }

  // Extract game IDs.
  for (const match of input.matchAll(shortcodePatterns.game)) {
    const id = parseInt(match[1], 10);
    if (!isNaN(id)) entities.gameIds.push(id);
  }

  return {
    usernames: [...new Set(entities.usernames)],
    ticketIds: [...new Set(entities.ticketIds)],
    achievementIds: [...new Set(entities.achievementIds)],
    gameIds: [...new Set(entities.gameIds)],
  };
}
