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
    gameIds: [],
    hubIds: [],
    eventIds: [],
    ticketIds: [],
    usernames: [],
  };

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
  };
}
