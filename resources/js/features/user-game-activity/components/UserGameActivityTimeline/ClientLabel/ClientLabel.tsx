import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuMonitor } from 'react-icons/lu';

import { cn } from '@/common/utils/cn';
import { buildEmulatorClientLabel } from '@/features/user-game-activity/utils/buildEmulatorClientLabel';

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
    return (
      <ActivityBasicLabel
        Icon={LuMonitor}
        label={t('Unknown Emulator')}
        className="text-neutral-500 light:text-neutral-400"
      />
    );
  }

  const emulatorClientLabel = buildEmulatorClientLabel(session.parsedUserAgent);
  const isUnknown = emulatorClientLabel.includes('Unknown Unknown');

  return (
    <ActivityBasicLabel
      Icon={LuMonitor}
      label={isUnknown ? t('Unknown Emulator') : emulatorClientLabel}
      className={cn(
        'light:text-neutral-900',
        isUnknown ? 'text-neutral-500 light:text-neutral-400' : null,
      )}
    />
  );
};
