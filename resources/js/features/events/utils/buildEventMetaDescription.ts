import { formatDate } from '@/common/utils/l10n/formatDate';

export function buildEventMetaDescription(event: App.Platform.Data.Event): string {
  const formattedStart = event.activeFrom ? formatDate(event.activeFrom, 'll') : null;
  const formattedEnd = event.activeThrough ? formatDate(event.activeThrough, 'll') : null;
  const totalAchievements = event.eventAchievements?.length ?? 0;

  // Evergreen events are always ongoing and dates are just historical metadata.
  if (event.state === 'evergreen') {
    return `A non time limited event containing ${totalAchievements} achievements.`;
  }

  // Active events emphasize end date if available.
  if (event.state === 'active') {
    const endContext = formattedEnd ? ` until ${formattedEnd}` : '';

    return `An ongoing event${endContext} featuring ${totalAchievements} ${totalAchievements === 1 ? 'achievement' : 'achievements'}.`;
  }

  // For concluded events, show date range if available.
  const dateRange =
    formattedStart && formattedEnd ? ` that ran from ${formattedStart} to ${formattedEnd}` : '';

  return `A past event${dateRange} featuring ${totalAchievements} ${totalAchievements === 1 ? 'achievement' : 'achievements'}.`;
}
