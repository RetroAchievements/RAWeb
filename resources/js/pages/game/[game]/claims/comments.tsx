import { useTranslation } from 'react-i18next';

import { SEO } from '@/common/components/SEO';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { GameClaimsCommentsMainRoot } from '@/features/comments/GameClaimsCommentsMainRoot';

const GameClaimsComments: AppPage<App.Community.Data.GameClaimsCommentsPageProps> = ({ game }) => {
  const { t } = useTranslation();

  return (
    <>
      <SEO
        title={t('Claim Comments - {{gameTitle}}', { gameTitle: game.title })}
        description={`Internal discussion about claims for ${game.title}`}
        ogImage={game.badgeUrl}
      />

      <AppLayout.Main>
        <GameClaimsCommentsMainRoot />
      </AppLayout.Main>
    </>
  );
};

GameClaimsComments.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default GameClaimsComments;
