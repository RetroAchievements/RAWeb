import { useTranslation } from 'react-i18next';

import { SEO } from '@/common/components/SEO';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { GameSetRequestsRoot } from '@/features/games/components/+requests';

const SetRequestorsPage: AppPage<App.Community.Data.GameClaimsCommentsPageProps> = ({ game }) => {
  const { t } = useTranslation();

  return (
    <>
      <SEO
        title={t('Set Requests - {{gameTitle}}', { gameTitle: game.title })}
        description={`A list of set requests for ${game.title}.`}
      />

      <div className="container">
        <AppLayout.Main className="min-h-[400px]">
          <GameSetRequestsRoot />
        </AppLayout.Main>
      </div>
    </>
  );
};

SetRequestorsPage.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default SetRequestorsPage;
