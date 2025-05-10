import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { FaLink } from 'react-icons/fa6';
import { useCopyToClipboard } from 'react-use';
import { route } from 'ziggy-js';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import { toastMessage } from '@/common/components/+vendor/BaseToaster';
import {
  BaseTooltip,
  BaseTooltipContent,
  BaseTooltipTrigger,
} from '@/common/components/+vendor/BaseTooltip';

interface ForumPostCopyLinkButtonProps {
  comment: App.Data.ForumTopicComment;
  topic: App.Data.ForumTopic;
}

export const ForumPostCopyLinkButton: FC<ForumPostCopyLinkButtonProps> = ({ comment, topic }) => {
  const { t } = useTranslation();

  const [, copyToClipboard] = useCopyToClipboard();

  const handleClick = () => {
    copyToClipboard(
      route('forum-topic.show', { topic: topic.id, _query: { comment: comment.id } }) +
        '#' +
        comment.id,
    );

    toastMessage.success(t('Copied!'));
  };

  return (
    <BaseTooltip>
      <BaseTooltipTrigger asChild>
        <BaseButton
          aria-label={t('Copy post link')}
          size="sm"
          className="max-h-[22px] !p-1 !text-2xs lg:!text-xs"
          onClick={handleClick}
        >
          <FaLink className="!size-3" />
        </BaseButton>
      </BaseTooltipTrigger>

      <BaseTooltipContent className="text-xs">{t('Copy post link')}</BaseTooltipContent>
    </BaseTooltip>
  );
};
