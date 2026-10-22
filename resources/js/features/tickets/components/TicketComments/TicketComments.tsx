import { router } from '@inertiajs/react';
import dayjs from 'dayjs';
import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { CommentList } from '@/common/components/CommentList';
import { usePageProps } from '@/common/hooks/usePageProps';
import type { CommentListInterleavedItem } from '@/common/models';

import { TicketChangelogEntry } from '../TicketChangelogEntry';
import { TicketReport } from '../TicketReport';

export const TicketComments: FC = () => {
  const { can, changelog, comments, reportBody, ticket } =
    usePageProps<App.Platform.Data.TicketShowPageProps>();
  const { t } = useTranslation();

  // A reporter leaving a reply can mutate ticket state, so we'll invalidate the client-side cache altogether.
  const reloadPage = () => {
    router.reload();
  };

  const { footerEntries, rowEntries } = groupChangelogEntries(comments, changelog);

  const mappedRowEntries: CommentListInterleavedItem[] = rowEntries.map((entry, index) => ({
    createdAt: entry.createdAt,
    key: `changelog-${index}`,
    node: <TicketChangelogEntry entry={entry} ticketableType={ticket.ticketableType} />,
  }));

  const renderCommentFooter = (comment: App.Community.Data.Comment) => {
    const entries = footerEntries.get(comment.id);
    if (!entries) {
      return null;
    }

    return (
      <div className="flex flex-col">
        {entries.map((entry, index) => (
          <div key={index} className="relative py-0.5 pl-4.5">
            <span
              aria-hidden={true}
              className="absolute -top-1 left-1 h-[calc(50%+0.25rem)] w-2.5 rounded-bl-md border-b border-l border-neutral-600 light:border-neutral-400"
            />
            {index < entries.length - 1 ? (
              <span
                aria-hidden={true}
                className="absolute -top-1 bottom-0 left-1 border-l border-neutral-600 light:border-neutral-400"
              />
            ) : null}

            <TicketChangelogEntry
              entry={entry}
              ticketableType={ticket.ticketableType}
              variant="commentFooter"
            />
          </div>
        ))}
      </div>
    );
  };

  return (
    <section id="comments" className="flex flex-col gap-2">
      <h2 className="mb-0 border-0 text-lg font-semibold">{t('Comments')}</h2>

      {reportBody ? <TicketReport reportBody={reportBody} ticket={ticket} /> : null}

      <CommentList
        comments={comments}
        canComment={!!can.createTicketComments}
        hasEmptyState={!reportBody}
        interleavedItems={mappedRowEntries}
        footerSlot={renderCommentFooter}
        commentableId={ticket.id}
        commentableType="trigger.ticket.comment"
        onDeleteSuccess={reloadPage}
        onSubmitSuccess={reloadPage}
      />
    </section>
  );
};

const commentFooterWindowMinutes = 5;

function groupChangelogEntries(
  comments: App.Community.Data.Comment[],
  changelog: App.Platform.Data.TicketChangelogEntry[],
) {
  const footerEntries = new Map<number, App.Platform.Data.TicketChangelogEntry[]>();
  const rowEntries: App.Platform.Data.TicketChangelogEntry[] = [];

  const timeline = [...comments, ...changelog].sort((a, b) =>
    dayjs(a.createdAt).diff(dayjs(b.createdAt)),
  );

  let previousComment: App.Community.Data.Comment | null = null;
  for (const item of timeline) {
    if ('payload' in item) {
      previousComment = item;
    } else if (
      previousComment &&
      item.user?.displayName === previousComment.user.displayName &&
      dayjs(item.createdAt).diff(dayjs(previousComment.createdAt), 'minute', true) <=
        commentFooterWindowMinutes
    ) {
      footerEntries.set(previousComment.id, [
        ...(footerEntries.get(previousComment.id) ?? []),
        item,
      ]);
    } else {
      rowEntries.push(item);
      previousComment = null;
    }
  }

  return { footerEntries, rowEntries };
}
