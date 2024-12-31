import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuDisc3 } from 'react-icons/lu';

import {
  BaseTooltip,
  BaseTooltipContent,
  BaseTooltipTrigger,
} from '@/common/components/+vendor/BaseTooltip';

interface HashLabelProps {
  session: App.Platform.Data.PlayerGameActivitySession;
}

export const HashLabel: FC<HashLabelProps> = ({ session }) => {
  const { t } = useTranslation();

  if (!session.gameHash) {
    return (
      <BaseTooltip>
        <BaseTooltipTrigger>
          <span>{t('Unknown Hash')}</span>
        </BaseTooltipTrigger>

        <BaseTooltipContent className="max-w-[280px]">
          <p className="text-xs">
            {t(
              "Either the user's emulator isn't reporting what hash they're using, the user's session was created before we started recording hashes, the user closed the game very quickly, or the user's hash was later unlinked from the game.",
            )}
          </p>
        </BaseTooltipContent>
      </BaseTooltip>
    );
  }

  if (!session.gameHash.name) {
    return <span>{session.gameHash.md5}</span>;
  }

  return (
    <div className="flex gap-2">
      {session.gameHash.isMultiDisc ? (
        <BaseTooltip>
          <BaseTooltipTrigger>
            <LuDisc3 className="size-4 text-cyan-300" />
            <span className="sr-only">
              {t(
                'This is a game with multiple discs. The hash shown here will only reflect the first disc loaded in the session.',
              )}
            </span>
          </BaseTooltipTrigger>

          <BaseTooltipContent className="max-w-[280px]">
            <p className="text-xs">
              {t(
                'This is a game with multiple discs. The hash shown here will only reflect the first disc loaded in the session.',
              )}
            </p>
          </BaseTooltipContent>
        </BaseTooltip>
      ) : null}

      <BaseTooltip>
        <BaseTooltipTrigger>
          <span className="flex text-left">{session.gameHash.name}</span>
        </BaseTooltipTrigger>

        <BaseTooltipContent>
          <p className="font-mono text-xs">{session.gameHash.md5}</p>
        </BaseTooltipContent>
      </BaseTooltip>
    </div>
  );
};
