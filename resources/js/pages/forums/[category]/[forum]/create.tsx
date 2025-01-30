import { useTranslation } from 'react-i18next';

import { SEO } from '@/common/components/SEO';
import { usePageProps } from '@/common/hooks/usePageProps';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { CreateForumTopicMainRoot } from '@/features/forums/components/CreateForumTopicMainRoot';

const CreateForumTopic: AppPage = () => {
  const { forum } = usePageProps<App.Data.CreateForumTopicPageProps>();

  const { t } = useTranslation();

  // some new stuff

  return (
    <>
      <SEO
        title={t('Start new topic')}
        description={`Start a new topic in the ${forum.title} forum`}
      />

      <AppLayout.Main>
        <CreateForumTopicMainRoot />
      </AppLayout.Main>
    </>
  );
};

CreateForumTopic.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default CreateForumTopic;
