import { SEO } from '@/common/components/SEO';
import { useHydrateShortcodeDynamicEntities } from '@/common/hooks/useHydrateShortcodeDynamicEntities';
import { usePageProps } from '@/common/hooks/usePageProps';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { ShowForumTopicMainRoot } from '@/features/forums/components/ShowForumTopicMainRoot';
import type { TranslatedString } from '@/types/i18next';

const ShowForumTopic: AppPage = () => {
  const { dynamicEntities, forumTopic, paginatedForumTopicComments } =
    usePageProps<App.Data.ShowForumTopicPageProps>();

  useHydrateShortcodeDynamicEntities(dynamicEntities);

  const currentPageFirstPost = paginatedForumTopicComments.items[0].body;
  const description = `${currentPageFirstPost.slice(0, 160)}${currentPageFirstPost.length > 160 ? '...' : ''}`;

  return (
    <>
      <SEO title={forumTopic.title as TranslatedString} description={description} />

      <AppLayout.Main>
        <ShowForumTopicMainRoot />
      </AppLayout.Main>
    </>
  );
};

ShowForumTopic.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default ShowForumTopic;
