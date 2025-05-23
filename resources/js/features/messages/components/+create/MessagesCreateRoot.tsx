import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { usePageProps } from '@/common/hooks/usePageProps';
import { useShortcodeBodyPreview } from '@/common/hooks/useShortcodeBodyPreview';

import { CreateMessageThreadForm } from '../CreateMessageThreadForm';
import { MessagePreviewContent } from '../MessagePreviewContent';
import { MessagesBreadcrumbs } from '../MessagesBreadcrumbs';

export const MessagesCreateRoot: FC = () => {
  const { auth, senderUser } = usePageProps<App.Community.Data.MessageThreadCreatePageProps>();

  const { t } = useTranslation();

  const { initiatePreview, previewContent } = useShortcodeBodyPreview();

  if (!auth) {
    return null;
  }

  const isDelegating = auth.user.displayName !== senderUser?.displayName;

  return (
    <div className="flex flex-col gap-4">
      <MessagesBreadcrumbs
        delegatedUserDisplayName={isDelegating ? senderUser?.displayName : undefined}
        t_currentPageLabel={t('Start new message thread')}
      />

      <CreateMessageThreadForm onPreview={initiatePreview} />

      {previewContent ? <MessagePreviewContent previewContent={previewContent} /> : null}
    </div>
  );
};
