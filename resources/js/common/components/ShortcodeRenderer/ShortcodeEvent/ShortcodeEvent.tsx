import { useAtomValue } from 'jotai';
import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { persistedEventsAtom } from '@/common/state/shortcode.atoms';

import { GameAvatar } from '../../GameAvatar';

interface ShortcodeEventProps {
  eventId: number;
}

export const ShortcodeEvent: FC<ShortcodeEventProps> = ({ eventId }) => {
  const { t } = useTranslation();

  const persistedEvents = useAtomValue(persistedEventsAtom);

  const foundEvent = persistedEvents?.find((event) => event.id === eventId);

  if (
    !foundEvent?.legacyGame?.title ||
    !foundEvent?.legacyGame?.id ||
    !foundEvent?.legacyGame?.badgeUrl
  ) {
    return null;
  }

  return (
    <span data-testid="event-embed" className="inline">
      <GameAvatar
        id={foundEvent.id}
        title={t('{{eventTitle}} (Events)', { eventTitle: foundEvent.legacyGame.title })}
        dynamicTooltipId={foundEvent.legacyGame.id}
        dynamicTooltipType="game"
        badgeUrl={foundEvent.legacyGame.badgeUrl}
        size={24}
        variant="inline"
        href={route('event.show', { event: foundEvent.id })}
      />
    </span>
  );
};
