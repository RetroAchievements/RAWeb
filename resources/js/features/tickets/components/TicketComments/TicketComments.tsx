import { router } from '@inertiajs/react';
import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { CommentList } from '@/common/components/CommentList';
import { usePageProps } from '@/common/hooks/usePageProps';

export const TicketComments: FC = () => {
  const { can, comments, ticket } = usePageProps<App.Platform.Data.TicketShowPageProps>();
  const { t } = useTranslation();

  // A reporter leaving a reply can mutate ticket state, so we'll invalidate the client-side cache altogether.
  const reloadPage = () => {
    router.reload();
  };

  return (
    <section id="comments" className="flex flex-col gap-2">
      <h2 className="mb-0 border-0 text-lg font-semibold">{t('Comments')}</h2>

      <CommentList
        comments={comments}
        canComment={!!can.createTicketComments}
        commentableId={ticket.id}
        commentableType="trigger.ticket.comment"
        onDeleteSuccess={reloadPage}
        onSubmitSuccess={reloadPage}
      />
    </section>
  );
};
