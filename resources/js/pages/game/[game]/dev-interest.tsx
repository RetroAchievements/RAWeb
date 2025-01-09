import { useTranslation } from 'react-i18next';

import { SEO } from '@/common/components/SEO';
import { usePageProps } from '@/common/hooks/usePageProps';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { DevInterestMainRoot } from '@/features/games/components/DevInterestMainRoot';

const GameDevInterest: AppPage = () => {
  const { game } = usePageProps<App.Platform.Data.DeveloperInterestPageProps>();

  const { t } = useTranslation();

  return (
    <>
      <SEO
        title={t('Developer Interest - {{gameTitle}}', { gameTitle: game.title })}
        description={`See developers who have expressed an interest in working on {{gameTitle}}.`}
        ogImage={game.badgeUrl}
      />

      <AppLayout.Main>
        <DevInterestMainRoot />
      </AppLayout.Main>
    </>
  );
};

GameDevInterest.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default GameDevInterest;
