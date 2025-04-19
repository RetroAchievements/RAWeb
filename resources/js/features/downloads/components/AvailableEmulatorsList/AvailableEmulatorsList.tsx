import type { FC } from 'react';

import { useVisibleEmulators } from '../../hooks/useVisibleEmulators';
import { DownloadableClientCard } from '../DownloadableClientCard';
import { AvailableEmulatorsEmptyState } from './AvailableEmulatorsEmptyState';

export const AvailableEmulatorsList: FC = () => {
  const { visibleEmulators } = useVisibleEmulators();

  if (!visibleEmulators.length) {
    return <AvailableEmulatorsEmptyState />;
  }

  return (
    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
      {visibleEmulators.map((visibleEmulator) => (
        <DownloadableClientCard
          key={`visible-emulator-${visibleEmulator.id}`}
          emulator={visibleEmulator}
        />
      ))}
    </div>
  );
};
