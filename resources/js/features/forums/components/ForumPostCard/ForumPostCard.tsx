import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { ShortcodeRenderer } from '@/common/components/ShortcodeRenderer';

interface ForumPostCardProps {
  body: string;
}

export const ForumPostCard: FC<ForumPostCardProps> = ({ body }) => {
  const { t } = useTranslation();

  return (
    <div>
      <div className="relative my-2">
        <div className="relative -mx-2 w-[calc(100%+16px)] rounded-lg bg-embed-highlight px-1 py-2 even:bg-embed sm:mx-0 sm:w-full lg:flex">
          <div className="border-neutral-700 px-0.5 lg:border-b-0 lg:border-r lg:py-2">
            <div className="flex w-full items-center lg:w-44 lg:flex-col lg:text-center" />
          </div>

          <div className="w-full py-2 lg:px-6 lg:py-2" style={{ wordBreak: 'break-word' }}>
            <div className="mb-4 flex w-full flex-col items-start justify-between gap-x-2 gap-y-2 sm:flex-row lg:mb-3">
              <p className="text-2xs leading-[14px] text-neutral-400">{t('Preview')}</p>
            </div>

            <div>
              <ShortcodeRenderer body={body} />
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};
