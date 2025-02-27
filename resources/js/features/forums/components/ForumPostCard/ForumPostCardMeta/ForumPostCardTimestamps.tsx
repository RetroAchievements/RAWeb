import dayjs from 'dayjs';
import utc from 'dayjs/plugin/utc';
import type { FC } from 'react';
import { Trans } from 'react-i18next';

import {
  BaseTooltip,
  BaseTooltipContent,
  BaseTooltipTrigger,
} from '@/common/components/+vendor/BaseTooltip';
import { usePageProps } from '@/common/hooks/usePageProps';
import { formatDate } from '@/common/utils/l10n/formatDate';
import { useDiffForHumans } from '@/common/utils/l10n/useDiffForHumans';

dayjs.extend(utc);

interface ForumPostCardTimestampsProps {
  comment: App.Data.ForumTopicComment;
}

export const ForumPostCardTimestamps: FC<ForumPostCardTimestampsProps> = ({ comment }) => {
  const { auth } = usePageProps<App.Data.ShowForumTopicPageProps>();
  const { diffForHumans } = useDiffForHumans();

  // Format timestamps based on user preference and recency.
  const formatTime = (date: string | null) => {
    if (!date) {
      return '';
    }

    if (auth?.user.preferences.prefersAbsoluteDates) {
      return formatDate(date, 'MMM DD, YYYY, HH:mm');
    }

    return dayjs.utc(date).isAfter(dayjs.utc().subtract(24, 'hour'))
      ? diffForHumans(comment.createdAt, dayjs.utc().toISOString())
      : formatDate(date, 'lll');
  };

  const createdLabel = formatTime(comment.createdAt);
  const editedLabel =
    comment.updatedAt !== comment.createdAt ? formatTime(comment.updatedAt) : null;

  const shouldShowCreatedTooltip =
    !auth?.user.preferences.prefersAbsoluteDates &&
    dayjs.utc(comment.createdAt).isAfter(dayjs.utc().subtract(24, 'hour'));

  const shouldShowEditedTooltip =
    editedLabel &&
    !auth?.user.preferences.prefersAbsoluteDates &&
    dayjs.utc(comment.updatedAt!).isAfter(dayjs.utc().subtract(24, 'hour'));

  return (
    <p className="smalltext !leading-[14px]">
      {shouldShowCreatedTooltip ? (
        <BaseTooltip>
          <BaseTooltipTrigger>
            <span>{createdLabel}</span>
          </BaseTooltipTrigger>

          <BaseTooltipContent className="text-xs" asChild>
            <span>{formatDate(comment.createdAt, 'lll')}</span>
          </BaseTooltipContent>
        </BaseTooltip>
      ) : (
        createdLabel
      )}

      {editedLabel ? (
        <>
          {', '}
          <span className="smalltext italic !leading-[14px]">
            <Trans
              i18nKey="<1>last</1> edited"
              components={{ 1: <span className="hidden sm:inline" /> }}
            />{' '}
            {shouldShowEditedTooltip ? (
              <BaseTooltip>
                <BaseTooltipTrigger>
                  <span className="italic">{editedLabel}</span>
                </BaseTooltipTrigger>

                <BaseTooltipContent className="text-xs not-italic" asChild>
                  <span>{formatDate(comment.updatedAt!, 'lll')}</span>
                </BaseTooltipContent>
              </BaseTooltip>
            ) : (
              <span className="italic">{editedLabel}</span>
            )}
          </span>
        </>
      ) : null}
    </p>
  );
};
