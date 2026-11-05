import type { FC } from 'react';

import {
  BaseTooltip,
  BaseTooltipContent,
  BaseTooltipTrigger,
} from '@/common/components/+vendor/BaseTooltip';
import { cn } from '@/common/utils/cn';

import { useTicketStateLabels } from '../../hooks/useTicketStateLabels';
import { TICKET_STATE_GLYPHS } from '../../utils/ticketStateGlyphs';

interface TicketStateGlyphProps {
  state: App.Community.Enums.TicketState;

  className?: string;
  hasTooltip?: boolean;
}

export const TicketStateGlyph: FC<TicketStateGlyphProps> = ({
  state,
  className,
  hasTooltip = true,
}) => {
  const label = useTicketStateLabels()[state];
  const { Icon, className: stateClassName } = TICKET_STATE_GLYPHS[state];

  const glyph = (
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
  );

  if (!hasTooltip) {
    return glyph;
  }

  return (
    <BaseTooltip>
      <BaseTooltipTrigger asChild>{glyph}</BaseTooltipTrigger>

      <BaseTooltipContent>{label}</BaseTooltipContent>
    </BaseTooltip>
  );
};
