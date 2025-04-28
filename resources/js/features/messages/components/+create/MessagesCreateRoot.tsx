import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { useShortcodeBodyPreview } from '@/common/hooks/useShortcodeBodyPreview';

import { CreateMessageThreadForm } from '../CreateMessageThreadForm';
import { MessagePreviewContent } from '../MessagePreviewContent';
import { MessagesBreadcrumbs } from '../MessagesBreadcrumbs';

export const MessagesCreateRoot: FC = () => {
  const { t } = useTranslation();

  const { initiatePreview, previewContent } = useShortcodeBodyPreview();

  return (
    <div className="flex flex-col gap-4">
      <MessagesBreadcrumbs t_currentPageLabel={t('Start new message thread')} />

      <CreateMessageThreadForm onPreview={initiatePreview} />

      {previewContent ? <MessagePreviewContent previewContent={previewContent} /> : null}
    </div>
  );
};
