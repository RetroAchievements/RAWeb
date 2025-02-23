import { router } from '@inertiajs/react';
import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import { toastMessage } from '@/common/components/+vendor/BaseToaster';
import { FullPaginator } from '@/common/components/FullPaginator';
import { ShortcodeRenderer } from '@/common/components/ShortcodeRenderer';
import { usePageProps } from '@/common/hooks/usePageProps';
import { useShortcodeBodyPreview } from '@/common/hooks/useShortcodeBodyPreview';
import type { TranslatedString } from '@/types/i18next';

import { useDeleteMessageThreadMutation } from '../../hooks/useDeleteMessageThreadMutation';
import { CreateMessageReplyForm } from '../CreateMessageReplyForm';
import { MessagesBreadcrumbs } from '../MessagesBreadcrumbs';
import { ReadableMessageCard } from '../ReadableMessageCard';

export const MessagesShowRoot: FC = () => {
  const { canReply, messageThread, paginatedMessages } =
    usePageProps<App.Community.Data.MessageThreadShowPageProps>();

  const { t } = useTranslation();

  const { initiatePreview, previewContent } = useShortcodeBodyPreview();

  const deleteMessageThreadMutation = useDeleteMessageThreadMutation();

  const handleDeleteClick = async () => {
    if (!confirm(t('Are you sure you want to delete this message thread?'))) {
      return;
    }

    await toastMessage.promise(deleteMessageThreadMutation.mutateAsync(messageThread), {
      loading: 'Deleting...',
      success: 'Deleted!',
      error: 'Something went wrong.',
    });

    router.visit(route('message-thread.index'));
  };

  const handlePageSelectValueChange = (newPageValue: number) => {
    router.visit(
      route('message-thread.show2', {
        messageThread: messageThread.id,
        _query: { page: newPageValue },
      }),
    );
  };

  return (
    <div className="flex flex-col gap-4">
      <div>
        <MessagesBreadcrumbs t_currentPageLabel={messageThread.title as TranslatedString} />
        <h1 className="text-h3 w-full self-end sm:mt-2.5 sm:!text-[2.0em]">
          {messageThread.title}
        </h1>

        <div className="flex items-center justify-between">
          <div>
            <BaseButton variant="destructive" size="sm" onClick={handleDeleteClick}>
              {t('Delete')}
            </BaseButton>
          </div>

          <FullPaginator
            onPageSelectValueChange={handlePageSelectValueChange}
            paginatedData={paginatedMessages}
          />
        </div>
      </div>

      <div className="flex flex-col gap-4">
        {paginatedMessages.items.map((message, messageIndex) => (
          <ReadableMessageCard key={`msg-${messageIndex}`} message={message} />
        ))}
      </div>

      {canReply ? (
        <div className="mt-8">
          <CreateMessageReplyForm onPreview={initiatePreview} />
        </div>
      ) : (
        <p className="text-center text-neutral-500 light:text-neutral-600">
          {t("You can't reply to this conversation right now.")}
        </p>
      )}

      {previewContent ? (
        <div data-testid="preview-content" className="mt-2">
          <div className="rounded bg-embed px-2.5 py-1.5">
            <div>
              <p className="text-neutral-300 light:text-neutral-700">{t('Preview')}</p>
            </div>

            <hr className="my-2 w-full border-embed-highlight" />

            <div style={{ wordBreak: 'break-word' }}>
              <ShortcodeRenderer body={previewContent} />
            </div>
          </div>
        </div>
      ) : null}

      <div className="mt-3 flex w-full justify-end">
        <FullPaginator
          onPageSelectValueChange={handlePageSelectValueChange}
          paginatedData={paginatedMessages}
        />
      </div>
    </div>
  );
};
