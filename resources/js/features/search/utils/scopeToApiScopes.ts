import type { SearchApiScope, SearchMode } from '@/common/models';

export const scopeToApiScopes: Record<SearchMode, SearchApiScope[]> = {
  all: ['users', 'games', 'hubs', 'events', 'achievements', 'forum_comments', 'comments'],
  games: ['games'],
  users: ['users'],
  hubs: ['hubs'],
  achievements: ['achievements'],
  events: ['events'],
  forum_comments: ['forum_comments'],
  comments: ['comments'],
};
