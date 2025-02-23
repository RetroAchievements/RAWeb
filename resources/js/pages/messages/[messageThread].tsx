import { SEO } from '@/common/components/SEO';
import { useHydrateShortcodeDynamicEntities } from '@/common/hooks/useHydrateShortcodeDynamicEntities';
import { usePageProps } from '@/common/hooks/usePageProps';
import { AppLayout } from '@/common/layouts/AppLayout';
import type { AppPage } from '@/common/models';
import { MessagesShowRoot } from '@/features/messages/components/+show';
import type { TranslatedString } from '@/types/i18next';

const MessageThread: AppPage = () => {
  const { dynamicEntities, messageThread } =
    usePageProps<App.Community.Data.MessageThreadShowPageProps>();

  useHydrateShortcodeDynamicEntities(dynamicEntities);

  return (
    <>
      <SEO
        title={messageThread.title as TranslatedString}
        description={`View the ${messageThread.title} message thread`}
      />

      <AppLayout.Main>
        <MessagesShowRoot />
      </AppLayout.Main>
    </>
  );
};

MessageThread.layout = (page) => <AppLayout withSidebar={false}>{page}</AppLayout>;

export default MessageThread;
