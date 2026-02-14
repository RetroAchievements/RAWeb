import { useMemo } from 'react';

import { useFormatNumber } from '@/common/hooks/useFormatNumber';

export function useAchievementMetaDescription(
  achievement: App.Platform.Data.Achievement,
  game: App.Platform.Data.Game,
): string {
  const { formatNumber } = useFormatNumber();

  return useMemo(() => {
    const pointsLabel = achievement.points === 1 ? 'point' : 'points';

    let bracketText = `${achievement.points} ${pointsLabel}`;
    if (achievement.type) {
      const typeLabel = typeLabels[achievement.type];
      if (typeLabel) {
        bracketText += `, ${typeLabel}`;
      }
    }

    const localizedWinnerCount = formatNumber(achievement.unlocksTotal);
    const winnerCountLabel = achievement.unlocksTotal === 1 ? 'player' : 'players';

    return `${achievement.description} [${bracketText}], won by ${localizedWinnerCount} ${winnerCountLabel} - ${game.title} for ${game.system!.name}`;
  }, [
    achievement.description,
    achievement.points,
    achievement.type,
    achievement.unlocksTotal,
    formatNumber,
    game.system,
    game.title,
  ]);
}

const typeLabels: Record<string, string> = {
  progression: 'Progression',
  win_condition: 'Win Condition',
  missable: 'Missable',
};
