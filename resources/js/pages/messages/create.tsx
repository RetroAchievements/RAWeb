import { useTranslation } from 'react-i18next';

import { SEO } from '@/common/components/SEO';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { MessagesCreateRoot } from '@/features/messages/components/+create';

const MessageCreate: AppPage = () => {
  const { t } = useTranslation();

  return (
    <>
      <SEO title={t('Create New Message')} description="Create a new message" />

      <AppLayout.Main>
        <MessagesCreateRoot />
      </AppLayout.Main>
    </>
  );
};

MessageCreate.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default MessageCreate;
