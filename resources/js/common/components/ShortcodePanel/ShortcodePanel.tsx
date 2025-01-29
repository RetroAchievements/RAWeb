import type { FC } from 'react';

import { useShortcodeInjection } from '../../hooks/useShortcodeInjection';
import { useShortcodesList } from '../../hooks/useShortcodesList';
import { BaseButton } from '../+vendor/BaseButton';
import { BaseTooltip, BaseTooltipContent, BaseTooltipTrigger } from '../+vendor/BaseTooltip';

export const ShortcodePanel: FC = () => {
  const { shortcodesList } = useShortcodesList();

  const { injectShortcode } = useShortcodeInjection({ fieldName: 'body' });

  return (
    <div className="w-full rounded bg-embed p-2">
      <div className="flex flex-wrap gap-2">
        {shortcodesList.map((shortcode) => (
          <BaseTooltip key={shortcode.t_label}>
            <BaseTooltipTrigger asChild>
              <BaseButton size="sm" type="button" onClick={() => injectShortcode(shortcode)}>
                <span className="sr-only">{shortcode.t_label}</span>
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
