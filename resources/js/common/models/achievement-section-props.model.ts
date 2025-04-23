import type { ReactNode } from 'react';

export interface AchievementSectionProps {
  achievementCount: number;
  children: ReactNode;
  isInitiallyOpened: boolean;
  title: string;
}
