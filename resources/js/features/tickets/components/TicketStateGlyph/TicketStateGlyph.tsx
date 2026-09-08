import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import {
  BaseTooltip,
  BaseTooltipContent,
  BaseTooltipTrigger,
} from '@/common/components/+vendor/BaseTooltip';
import { cn } from '@/common/utils/cn';

import { getTicketStateLabel } from '../../utils/getTicketStateLabel';
import { TICKET_STATE_GLYPHS } from '../../utils/ticketStateGlyphs';

interface TicketStateGlyphProps {
  state: App.Community.Enums.TicketState;

  className?: string;
}

export const TicketStateGlyph: FC<TicketStateGlyphProps> = ({ state, className }) => {
  const { t } = useTranslation();

  const label = getTicketStateLabel(state, t);
  const { Icon, className: stateClassName } = TICKET_STATE_GLYPHS[state];

  return (
    <BaseTooltip>
      <BaseTooltipTrigger asChild>
        <span
          role="img"
          aria-label={label}
          data-state={state}
          className={cn(
            'relative z-10 flex flex-none items-center justify-center',
            'before:absolute before:inset-[-0.5em] before:content-[""]',

            stateClassName,
            className,
          )}
        >
          <Icon className="size-4" aria-hidden="true" />
        </span>
      </BaseTooltipTrigger>

      <BaseTooltipContent>{label}</BaseTooltipContent>
    </BaseTooltip>
  );
};
