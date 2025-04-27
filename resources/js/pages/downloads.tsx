import { useHydrateAtoms } from 'jotai/utils';
import { useTranslation } from 'react-i18next';

import { SEO } from '@/common/components/SEO';
import { usePageProps } from '@/common/hooks/usePageProps';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { DownloadsMainRoot } from '@/features/downloads/components/+root';
import {
  selectedPlatformIdAtom,
  selectedSystemIdAtom,
} from '@/features/downloads/state/downloads.atoms';

const Downloads: AppPage = () => {
  const { userDetectedPlatformId, userSelectedSystemId } =
    usePageProps<App.Http.Data.DownloadsPageProps>();

  const { t } = useTranslation();

  useHydrateAtoms([
    [selectedSystemIdAtom, userSelectedSystemId],
    [selectedPlatformIdAtom, userDetectedPlatformId],
    //
  ]);

  return (
    <>
      <SEO
        title={t('Downloads')}
        description="Get started with RetroAchievements by downloading an emulator with built-in RetroAchievements support."
      />

      <AppLayout.Main>
        <DownloadsMainRoot />
      </AppLayout.Main>
    </>
  );
};

Downloads.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default Downloads;
