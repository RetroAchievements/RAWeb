import { useTranslation } from 'react-i18next';

import { SEO } from '@/common/components/SEO';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { MessagesRoot } from '@/features/messages/components/+root';

const Messages: AppPage = () => {
  const { t } = useTranslation();

  return (
    <>
      <SEO title={t('Messages')} description="Your inbox messages" />

      <AppLayout.Main>
        <MessagesRoot />
      </AppLayout.Main>
    </>
  );
};

Messages.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default Messages;
