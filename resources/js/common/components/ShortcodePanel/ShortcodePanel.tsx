import type { FC } from 'react';

import { cn } from '@/common/utils/cn';

import { useShortcodeInjection } from '../../hooks/useShortcodeInjection';
import { useShortcodesList } from '../../hooks/useShortcodesList';
import { BaseButton } from '../+vendor/BaseButton';
import { BaseTooltip, BaseTooltipContent, BaseTooltipTrigger } from '../+vendor/BaseTooltip';

interface ShortcodePanelProps {
  className?: string;
}

export const ShortcodePanel: FC<ShortcodePanelProps> = ({ className }) => {
  const { shortcodesList } = useShortcodesList();

  const { injectShortcode } = useShortcodeInjection({ fieldName: 'body' });

  return (
    <div className={cn('w-full rounded bg-embed p-2', className)}>
      <div className="flex flex-wrap gap-2">
        {shortcodesList.map((shortcode, shortcodeIndex) => (
          <BaseTooltip key={`shortcode-${shortcodeIndex}`}>
            <BaseTooltipTrigger asChild>
              <BaseButton
                size="sm"
                type="button"
                onClick={() => injectShortcode(shortcode)}
                aria-label={shortcode.t_label}
              >
                <shortcode.icon className="size-4" />
              </BaseButton>
            </BaseTooltipTrigger>

            <BaseTooltipContent>{shortcode.t_label}</BaseTooltipContent>
          </BaseTooltip>
        ))}
      </div>
    </div>
  );
};
