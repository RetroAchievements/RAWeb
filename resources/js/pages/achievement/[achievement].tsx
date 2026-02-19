import { useHydrateAtoms } from 'jotai/utils';

import { SEO } from '@/common/components/SEO';
import { usePageProps } from '@/common/hooks/usePageProps';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { AchievementShowRoot } from '@/features/achievements/components/+show';
import { AchievementShowSidebarRoot } from '@/features/achievements/components/+show-sidebar';
import { useAchievementMetaDescription } from '@/features/achievements/hooks/useAchievementMetaDescription';
import { currentTabAtom } from '@/features/achievements/state/achievements.atoms';
import type { TranslatedString } from '@/types/i18next';

// The server always provides these fields on this page, but the generated
// type marks them optional since they're optional in other contexts.
// TODO can we generate this somehow with `composer types`? also for game.show
type HydratedAchievement = App.Platform.Data.Achievement & {
  description: string;
  points: number;
  unlocksTotal: number;
  game: App.Platform.Data.Game & { system: App.Platform.Data.System };
};

const AchievementShow: AppPage = () => {
  const { achievement, initialTab } = usePageProps<App.Platform.Data.AchievementShowPageProps>();

  useHydrateAtoms([
    [currentTabAtom, initialTab],
    //
  ]);

  const hydratedAchievement = achievement as HydratedAchievement;

  const metaDescription = useAchievementMetaDescription(
    hydratedAchievement,
    hydratedAchievement.game,
  );

  return (
    <>
      <SEO
        title={
          `${hydratedAchievement.title} - ${hydratedAchievement.game.title}` as TranslatedString
        }
        description={metaDescription}
        ogImage={hydratedAchievement.game.badgeUrl}
      />

      <AppLayout.Main>
        <AchievementShowRoot />
      </AppLayout.Main>

      <AppLayout.Sidebar>
        <AchievementShowSidebarRoot />
      </AppLayout.Sidebar>
    </>
  );
};

AchievementShow.layout = (page) => <AppLayout withSidebar={true}>{page}</AppLayout>;

export default AchievementShow;
