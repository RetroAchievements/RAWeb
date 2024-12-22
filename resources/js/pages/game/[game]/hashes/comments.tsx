import { useTranslation } from 'react-i18next';

import { SEO } from '@/common/components/SEO';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { GameHashesCommentsMainRoot } from '@/features/comments/GameHashesCommentsMainRoot';

const GameHashesComments: AppPage<App.Community.Data.GameHashesCommentsPageProps> = ({ game }) => {
  const { t } = useTranslation();

  return (
    <>
      <SEO
        title={t('Hash Comments - {{gameTitle}}', { gameTitle: game.title })}
        description={`Internal discussion about the hashes for ${game.title}`}
        ogImage={game.badgeUrl}
      />

      <AppLayout.Main>
        <GameHashesCommentsMainRoot />
      </AppLayout.Main>
    </>
  );
};

GameHashesComments.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default GameHashesComments;
