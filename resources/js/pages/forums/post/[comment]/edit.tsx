import { useTranslation } from 'react-i18next';

import { SEO } from '@/common/components/SEO';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { EditPostMainRoot } from '@/features/forums/components/EditPostMainRoot';

const ForumTopicCommentEdit: AppPage = () => {
  const { t } = useTranslation();

  return (
    <>
      <SEO title={t('Edit Post')} description="Edit a forum post you've made" />

      <AppLayout.Main>
        <EditPostMainRoot />
      </AppLayout.Main>
    </>
  );
};

ForumTopicCommentEdit.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default ForumTopicCommentEdit;
