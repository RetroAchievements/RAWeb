import { useTranslation } from 'react-i18next';

import { SEO } from '@/common/components/SEO';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { PatreonSupportersRoot } from '@/features/patreon-supporters/components/+root';

const PatreonSupporters: AppPage = () => {
  const { t } = useTranslation();

  return (
    <>
      <SEO
        title={t('Patreon Supporters')}
        description="A list of all our Patreon supporters who help keep RetroAchievements running."
      />

      <div className="container">
        <AppLayout.Main className="min-h-[4000px]">
          <PatreonSupportersRoot />
        </AppLayout.Main>
      </div>
    </>
  );
};

PatreonSupporters.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default PatreonSupporters;
