import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { BaseButton } from '@/common/components/+vendor/BaseButton';

interface BlockedPostNoticeProps {
  authorDisplayName?: string;
  onReveal?: () => void;
}

export const BlockedPostNotice: FC<BlockedPostNoticeProps> = ({ authorDisplayName, onReveal }) => {
  const { t } = useTranslation();

  return (
    <div
      data-testid="blocked-post-notice"
      className="flex w-full items-center justify-between gap-3 px-2 py-1"
    >
      <p className="text-xs text-neutral-400 light:text-neutral-500">
        {onReveal && authorDisplayName
          ? t('Hidden post from {{displayName}}', { displayName: authorDisplayName })
          : t('Hidden post')}
      </p>

      {onReveal ? (
        <BaseButton
          type="button"
          size="sm"
          onClick={onReveal}
          className="max-h-5.5 shrink-0 p-1! text-2xs! lg:text-xs!"
        >
          {t('Show post')}
        </BaseButton>
      ) : null}
    </div>
  );
};
