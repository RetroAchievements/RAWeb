import { useTranslation } from 'react-i18next';

import { SEO } from '@/common/components/SEO';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { MessagesIndexRoot } from '@/features/messages/components/+index';

const Messages: AppPage = () => {
  const { t } = useTranslation();

  return (
    <>
      <SEO title={t('Messages')} description="Your inbox messages" />

      <AppLayout.Main>
        <MessagesIndexRoot />
      </AppLayout.Main>
    </>
  );
};

Messages.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default Messages;
