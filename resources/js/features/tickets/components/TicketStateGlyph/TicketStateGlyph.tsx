import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import type { IconType } from 'react-icons';
import { LuCircle, LuCircleCheck, LuCircleDot, LuCircleSlash, LuCircleX } from 'react-icons/lu';

import {
  BaseTooltip,
  BaseTooltipContent,
  BaseTooltipTrigger,
} from '@/common/components/+vendor/BaseTooltip';
import { cn } from '@/common/utils/cn';

import { getTicketStateLabel } from '../../utils/getTicketStateLabel';

interface TicketStateGlyphProps {
  state: App.Community.Enums.TicketState;

  className?: string;
}

/**
 * Each state has a designated shape and color. This is a deliberate a11y choice to
 * ensure the glyphs remain distinct for the ~10% of users who are colorblind.
 */
const stateGlyphs: Record<App.Community.Enums.TicketState, { Icon: IconType; className: string }> =
  {
    open: { Icon: LuCircle, className: 'text-amber-400' },
    request: { Icon: LuCircleDot, className: 'text-sky-300' },
    resolved: { Icon: LuCircleCheck, className: 'text-green-500' },
    closed: { Icon: LuCircleX, className: 'text-neutral-400' },
    quarantined: { Icon: LuCircleSlash, className: 'text-rose-500' },
  };

export const TicketStateGlyph: FC<TicketStateGlyphProps> = ({ state, className }) => {
  const { t } = useTranslation();

  const label = getTicketStateLabel(state, t);
  const { Icon, className: stateClassName } = stateGlyphs[state];

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
