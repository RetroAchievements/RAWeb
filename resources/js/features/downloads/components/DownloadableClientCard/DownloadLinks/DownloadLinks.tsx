import { useAtomValue } from 'jotai';
import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuDownload } from 'react-icons/lu';

import { baseButtonVariants } from '@/common/components/+vendor/BaseButton';
import { usePageProps } from '@/common/hooks/usePageProps';
import { selectedPlatformIdAtom } from '@/features/downloads/state/downloads.atoms';

interface DownloadLinksProps {
  emulator: App.Platform.Data.Emulator;
}

export const DownloadLinks: FC<DownloadLinksProps> = ({ emulator }) => {
  const { allPlatforms } = usePageProps<App.Http.Data.DownloadsPageProps>();

  const { t } = useTranslation();

  const selectedPlatformId = useAtomValue(selectedPlatformIdAtom);

  if (!emulator.downloadUrl && !emulator.downloadX64Url && !emulator.downloads?.length) {
    return null;
  }

  const selectedPlatform =
    allPlatforms.find((p) => p.id === selectedPlatformId) ||
    (allPlatforms.find((p) => p.name === 'Windows') as App.Platform.Data.Platform);

  const { url, x64Url } = getVisibleDownloadUrls(emulator, selectedPlatform);

  return (
    <div className="flex w-full flex-col gap-2 xl:flex-row">
      {x64Url ? (
        <a
          className={baseButtonVariants({ size: 'sm', className: 'w-full' })}
          href={x64Url}
          target="_blank"
        >
          <LuDownload className="mr-1.5 size-4" />
          {t('Download (x64)')}
        </a>
      ) : null}

      <a
        className={baseButtonVariants({ size: 'sm', className: 'w-full' })}
        href={url}
        target="_blank"
      >
        <LuDownload className="mr-1.5 size-4" />
        {selectedPlatform.name !== 'Windows'
          ? t('Download for {{platformName}}', { platformName: selectedPlatform.name })
          : t('Download')}
      </a>
    </div>
  );
};

function getVisibleDownloadUrls(
  emulator: App.Platform.Data.Emulator,
  selectedPlatform: App.Platform.Data.Platform,
): { url: string; x64Url?: string } {
  const isWindows = selectedPlatform.name === 'Windows';
  const result: { url: string; x64Url?: string } = { url: '' };

  // Check if there's a platform-specific override in the downloads array.
  const platformSpecificDownload = emulator.downloads?.find(
    (download) => download.platformId === selectedPlatform.id,
  );

  // Set the main URL - either from platform-specific override or default.
  if (platformSpecificDownload) {
    result.url = platformSpecificDownload.url;
  } else if (emulator.downloadUrl) {
    result.url = emulator.downloadUrl;
  }

  // Add the x64 URL for Windows if it exists.
  if (isWindows && emulator.downloadX64Url) {
    result.x64Url = emulator.downloadX64Url;
  }

  return result;
}
