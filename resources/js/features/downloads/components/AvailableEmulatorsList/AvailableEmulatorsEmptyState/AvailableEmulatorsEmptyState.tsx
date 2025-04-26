import { useAtomValue } from 'jotai';
import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { EmptyState } from '@/common/components/EmptyState';
import { usePageProps } from '@/common/hooks/usePageProps';
import {
  selectedPlatformIdAtom,
  selectedSystemIdAtom,
} from '@/features/downloads/state/downloads.atoms';

export const AvailableEmulatorsEmptyState: FC = () => {
  const { allPlatforms, allSystems } = usePageProps<App.Http.Data.DownloadsPageProps>();

  const { t } = useTranslation();

  const selectedPlatformId = useAtomValue(selectedPlatformIdAtom);
  const selectedSystemId = useAtomValue(selectedSystemIdAtom);

  const selectedPlatform = allPlatforms.find((p) => p.id === selectedPlatformId);
  const selectedSystem = allSystems.find((s) => s.id === selectedSystemId);

  return (
    <div className="rounded-lg bg-embed">
      <EmptyState>
        {selectedSystem && selectedPlatform
          ? t("There aren't any {{systemName}} emulators available for {{platformName}} yet.", {
              systemName: selectedSystem.name,
              platformName: selectedPlatform.name,
            })
          : null}

        {selectedSystem && !selectedPlatform
          ? t("There aren't any {{systemName}} emulators available yet.", {
              systemName: selectedSystem.name,
            })
          : null}

        {selectedPlatform && !selectedSystem
          ? t("There aren't any emulators available for {{platformName}} yet.", {
              platformName: selectedPlatform.name,
            })
          : null}

        {!selectedPlatform && !selectedSystem
          ? t("There aren't any emulators available yet.")
          : null}
      </EmptyState>
    </div>
  );
};
