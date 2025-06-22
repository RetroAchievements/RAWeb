import { SEO } from '@/common/components/SEO';
import { usePageProps } from '@/common/hooks/usePageProps';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { GameShowMainRoot } from '@/features/games/components/+show';
import { GameShowSidebarRoot } from '@/features/games/components/+show-sidebar';
import { buildGameMetaDescription } from '@/features/games/utils/buildGameMetaDescription';
import type { TranslatedString } from '@/types/i18next';

const GameShow: AppPage = () => {
  const { game } = usePageProps<App.Platform.Data.GameShowPageProps>();

  const title = `${game.title} (${game.system!.name})`;

  return (
    <>
      <SEO
        title={title as TranslatedString}
        description={buildGameMetaDescription(game)}
        ogImage={game!.badgeUrl}
      />

      <AppLayout.Main>
        <GameShowMainRoot />
      </AppLayout.Main>

      <AppLayout.Sidebar>
        <GameShowSidebarRoot />
      </AppLayout.Sidebar>
    </>
  );
};

GameShow.layout = (page) => <AppLayout withSidebar={true}>{page}</AppLayout>;

export default GameShow;
