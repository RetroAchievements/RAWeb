import { Head } from '@inertiajs/react';

import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { HashesMainRoot } from '@/features/games/components/HashesMainRoot';

const Hashes: AppPage<App.Platform.Data.GameHashesPageProps> = ({ game, hashes }) => {
  return (
    <>
      <Head title={`Supported Game Files - ${game.title}`}>
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
