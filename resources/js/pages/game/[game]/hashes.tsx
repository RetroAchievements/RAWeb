import { useTranslation } from 'react-i18next';

import { SEO } from '@/common/components/SEO';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { HashesMainRoot } from '@/features/games/components/HashesMainRoot';

const Hashes: AppPage<App.Platform.Data.GameHashesPageProps> = ({ game, hashes }) => {
  const { t } = useTranslation();

  return (
    <>
      <SEO
        title={t('Supported Game Files - {{gameTitle}}', { gameTitle: game.title })}
        description={`View the ${hashes.length} supported ROM ${hashes.length === 1 ? 'hash' : 'hashes'} for ${game.title} achievements. Also find details on patch downloads.`}
        ogImage={game.badgeUrl}
      />

      <AppLayout.Main>
        <HashesMainRoot />
      </AppLayout.Main>
    </>
  );
};

Hashes.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default Hashes;
