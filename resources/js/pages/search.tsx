import { useTranslation } from 'react-i18next';

import { SEO } from '@/common/components/SEO';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { SearchMainRoot } from '@/features/search/components/+root';

const Search: AppPage = () => {
  const { t } = useTranslation();

  return (
    <>
      <SEO
        title={t('Search')}
        description="Search for games, users, achievements, hubs, events, and more."
      />

      <AppLayout.Main>
        <SearchMainRoot />
      </AppLayout.Main>
    </>
  );
};

Search.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default Search;
