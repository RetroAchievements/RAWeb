import type { GameShowTab } from '../models';

export function getInitialMobileTab(tabQueryParam?: string): GameShowTab {
  const allowedTabs: string[] = ['achievements', 'community', 'info', 'stats'];

  if (!tabQueryParam || !allowedTabs.includes(tabQueryParam)) {
    return 'achievements';
  }

  return tabQueryParam as GameShowTab;
}
