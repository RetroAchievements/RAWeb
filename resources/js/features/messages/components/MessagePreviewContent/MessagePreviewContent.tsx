import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { ShortcodeRenderer } from '@/common/components/ShortcodeRenderer';

interface MessagePreviewContentProps {
  previewContent: string;
}

export const MessagePreviewContent: FC<MessagePreviewContentProps> = ({ previewContent }) => {
  const { t } = useTranslation();

  return (
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
  );
};
