import { Head } from '@inertiajs/react';
import { useLaravelReactI18n } from 'laravel-react-i18n';

import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { HashesMainRoot } from '@/features/games/components/HashesMainRoot';

const Hashes: AppPage<App.Platform.Data.GameHashesPageProps> = ({ game, hashes }) => {
  const { t } = useLaravelReactI18n();

  return (
    <>
      <Head title={t('Supported Game Files - :gameTitle')}>
        <meta
          name="description"
          content={`View the ${hashes.length} supported ROM ${hashes.length === 1 ? 'hash' : 'hashes'} for ${game.title} achievements. Access additional details on hash generation and patch downloads.`}
        />
      </Head>

      <AppLayout.Main>
        <HashesMainRoot />
      </AppLayout.Main>
    </>
  );
};

Hashes.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default Hashes;
