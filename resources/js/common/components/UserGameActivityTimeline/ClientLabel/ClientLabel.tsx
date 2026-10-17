import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuMonitor } from 'react-icons/lu';

import {
  BaseTooltip,
  BaseTooltipContent,
  BaseTooltipTrigger,
} from '@/common/components/+vendor/BaseTooltip';
import { buildEmulatorClientLabel } from '@/common/utils/buildEmulatorClientLabel';

import { ActivityBasicLabel } from '../../ActivityBasicLabel';
import { ActivityTooltipWrapper } from '../../ActivityTooltipWrapper';
import { ManualUnlockLabel } from './ManualUnlockLabel';

interface ClientLabelProps {
  session: App.Platform.Data.PlayerGameActivitySession;
}

export const ClientLabel: FC<ClientLabelProps> = ({ session }) => {
  const { t } = useTranslation();

  if (session.type === 'reconstructed') {
    return (
      <ActivityTooltipWrapper
        Icon={LuMonitor}
        label={t('Reconstructed Session')}
        t_tooltip={t(
          'This session is missing emulator, playtime, and rich presence information. The session was automatically reconstructed by the server with what metadata we have.',
        )}
        className="text-neutral-500 light:text-neutral-400"
      />
    );
  }

  if (session.type === 'manual-unlock' && session.events?.[0].unlocker) {
    return <ManualUnlockLabel unlocker={session.events[0].unlocker} />;
  }

  if (!session.userAgent || !session.parsedUserAgent) {
    if (session.type === 'reset') {
      return (
        <ActivityBasicLabel
          Icon={LuMonitor}
          label={t('No Emulator')}
          className="text-neutral-500 light:text-neutral-400"
        />
      );
    }

    return (
      <ActivityBasicLabel
        Icon={LuMonitor}
        label={t('Unknown Emulator')}
        className="text-neutral-500 light:text-neutral-400"
      />
    );
  }

  const emulatorClientLabel = buildEmulatorClientLabel({ ...session.parsedUserAgent, os: null });
  const isUnknown = emulatorClientLabel.includes('Unknown Unknown');

  return (
    <BaseTooltip>
      <BaseTooltipTrigger className="text-left">
        {isUnknown ? (
          <ActivityBasicLabel
            Icon={LuMonitor}
            label={t('Unknown Emulator')}
            className="text-neutral-500 light:text-neutral-400"
          />
        ) : (
          <div className="flex items-start gap-1.5 light:text-neutral-900">
            <LuMonitor className="mt-0.5 size-4 min-w-5 light:text-neutral-400" />

            <div className="flex flex-col">
              <span>{emulatorClientLabel}</span>

              {session.parsedUserAgent.os ? (
                <span className="text-xs text-neutral-400 light:text-neutral-600">
                  {session.parsedUserAgent.os}
                </span>
              ) : null}
            </div>
          </div>
        )}
      </BaseTooltipTrigger>

      <BaseTooltipContent>
        <p className="text-xs">{session.userAgent}</p>
      </BaseTooltipContent>
    </BaseTooltip>
  );
};
