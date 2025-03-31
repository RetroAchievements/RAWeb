import type { FC } from 'react';
import { Trans } from 'react-i18next';
import { LuNetwork } from 'react-icons/lu';

import { BaseChip } from '@/common/components/+vendor/BaseChip';
import {
  BaseTooltip,
  BaseTooltipContent,
  BaseTooltipTrigger,
} from '@/common/components/+vendor/BaseTooltip';
import { GameAvatar } from '@/common/components/GameAvatar';
import { cleanHubTitle } from '@/common/utils/cleanHubTitle';

interface SharedHubReasonProps {
  relatedGame: App.Platform.Data.Game | null;
  relatedGameSet: App.Platform.Data.GameSet;
}

export const SharedHubReason: FC<SharedHubReasonProps> = ({ relatedGame, relatedGameSet }) => {
  return (
    <BaseChip
      data-testid="shared-hub-reason"
      className="flex gap-1 whitespace-nowrap py-1 text-neutral-300 light:text-neutral-900"
    >
      <LuNetwork className="size-[18px] lg:hidden xl:block" />

      <Trans
        i18nKey={
          relatedGame
            ? 'In <1><2><3>hub</3></2><4>{{hubName}}</4></1> with'
            : 'In same <1><2><3>hub</3></2><4>{{hubName}}</4></1>'
        }
        components={{
          1: <BaseTooltip></BaseTooltip>,
          2: <BaseTooltipTrigger></BaseTooltipTrigger>,
          3: <a href={route('hub.show', { gameSet: relatedGameSet.id })}>{'hub'}</a>,
          4: <BaseTooltipContent>{cleanHubTitle(relatedGameSet.title!)}</BaseTooltipContent>,
        }}
        values={{ hubName: cleanHubTitle(relatedGameSet.title!) }}
      />

      {relatedGame ? (
        <GameAvatar
          {...relatedGame}
          showLabel={false}
          size={24}
          wrapperClassName="ml-0.5 inline-block"
        />
      ) : null}
    </BaseChip>
  );
};
