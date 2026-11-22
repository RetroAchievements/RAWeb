import { route } from 'ziggy-js';

import { buildStructuredMessage } from './buildStructuredMessage';

export function buildManualUnlockRequestUrl(
  achievement: App.Platform.Data.Achievement,
  ticketId?: number,
): string {
  const { subject, message, templateKind } = buildStructuredMessage(achievement, 'manual-unlock');

  return route('message-thread.create', {
    to: 'UnlockTeam',
    subject,
    message: ticketId ? `${message}\nTicket: [ticket=${ticketId}]` : message,
    templateKind,
  });
}
