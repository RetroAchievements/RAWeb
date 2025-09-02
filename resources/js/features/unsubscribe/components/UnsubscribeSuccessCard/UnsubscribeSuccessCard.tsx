import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuUndo2 } from 'react-icons/lu';
import { route } from 'ziggy-js';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import { BaseCard, BaseCardContent } from '@/common/components/+vendor/BaseCard';
import { InertiaLink } from '@/common/components/InertiaLink';
import { usePageProps } from '@/common/hooks/usePageProps';

interface UnsubscribeSuccessCardProps {
  isMutationPending: boolean;
  onUndo: () => void;
}

export const UnsubscribeSuccessCard: FC<UnsubscribeSuccessCardProps> = ({
  isMutationPending,
  onUndo,
}) => {
  const { descriptionKey, descriptionParams, undoToken } =
    usePageProps<App.Community.Data.UnsubscribeShowPageProps>();
  const { t } = useTranslation();

  return (
    <BaseCard>
      <BaseCardContent className="flex flex-col gap-8 pt-8 text-center">
        {descriptionKey ? (
          <p className="text-balance sm:text-center">
            {/* eslint-disable-next-line @typescript-eslint/no-explicit-any -- this is fully dynamic */}
            {t(descriptionKey as any, descriptionParams ?? {})}
          </p>
        ) : null}

        <div className="flex w-full flex-col gap-4">
          {undoToken ? (
            <div className="flex flex-col items-center gap-2">
              <BaseButton
                onClick={onUndo}
                disabled={isMutationPending}
                size="sm"
                className="max-w-fit gap-1.5"
              >
                <LuUndo2 className="size-4" />
                {t('Undo')}
              </BaseButton>
            </div>
          ) : null}

          <InertiaLink href={route('settings.show')} prefetch="desktop-hover-only">
            {t('Manage All Email Preferences')}
          </InertiaLink>
        </div>
      </BaseCardContent>
    </BaseCard>
  );
};
