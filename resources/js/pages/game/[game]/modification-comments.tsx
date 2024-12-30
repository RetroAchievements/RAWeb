import { useTranslation } from 'react-i18next';

import { SEO } from '@/common/components/SEO';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { GameModificationCommentsMainRoot } from '@/features/comments/GameModificationCommentsMainRoot';

const GameModificationComments: AppPage<App.Community.Data.GameClaimsCommentsPageProps> = ({
  game,
}) => {
  const { t } = useTranslation();

  return (
    <>
      <SEO
        title={t('Modification Comments - {{gameTitle}}', { gameTitle: game.title })}
        description={`Internal discussion about game metadata modifications for ${game.title}`}
        ogImage={game.badgeUrl}
      />

      <AppLayout.Main>
        <GameModificationCommentsMainRoot />
      </AppLayout.Main>
    </>
  );
};

GameModificationComments.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default GameModificationComments;
