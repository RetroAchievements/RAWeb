import { useHydrateAtoms } from 'jotai/utils';
import { useTranslation } from 'react-i18next';

import { SEO } from '@/common/components/SEO';
import { usePageProps } from '@/common/hooks/usePageProps';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { cleanHubTitle } from '@/common/utils/cleanHubTitle';
import { HubMainRoot } from '@/features/game-list/components/HubMainRoot';
import { useHubPageMetaDescription } from '@/features/game-list/hooks/useHubPageMetaDescription';
import { isCurrentlyPersistingViewAtom } from '@/features/game-list/state/game-list.atoms';

const Hub: AppPage = () => {
  const { hub, persistedViewPreferences } = usePageProps<App.Platform.Data.HubPageProps>();

  const { t } = useTranslation();

  useHydrateAtoms([
    [isCurrentlyPersistingViewAtom, !!persistedViewPreferences],
    //
  ]);

  let pageTitle = t('All Hubs');
  const hubTitle = cleanHubTitle(hub.title!);
  if (hubTitle !== 'Central') {
    pageTitle = t('{{hubTitle}} (Hub)', { hubTitle });
  }

  const metaDescription = useHubPageMetaDescription();

  return (
    <>
      <SEO title={pageTitle} description={metaDescription} ogImage={hub.badgeUrl!} />

      <AppLayout.Main>
        <HubMainRoot />
      </AppLayout.Main>
    </>
  );
};

Hub.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default Hub;
