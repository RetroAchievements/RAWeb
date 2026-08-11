import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuQuote } from 'react-icons/lu';
import { route } from 'ziggy-js';

import { BaseButton } from '@/common/components/+vendor/BaseButton';
import {
  BaseTooltip,
  BaseTooltipContent,
  BaseTooltipTrigger,
} from '@/common/components/+vendor/BaseTooltip';

interface ForumPostQuoteButtonProps {
  comment: App.Data.ForumTopicComment;
  onQuote: (quoteText: string) => void;
  topic: App.Data.ForumTopic;
}

export const ForumPostQuoteButton: FC<ForumPostQuoteButtonProps> = ({
  comment,
  onQuote,
  topic,
}) => {
  const { t } = useTranslation();

  const handleClick = () => {
    const permalink =
      route('forum-topic.show', { topic: topic.id, _query: { comment: comment.id } }) +
      '#' +
      comment.id;

    const displayName = comment.user?.displayName ?? 'Deleted User';

    const quoteText = `[url=${permalink}][b]${displayName} wrote:[/b][/url]\n[quote]\n${comment.body}\n[/quote]\n\n`;

    onQuote(quoteText);
  };

  return (
    <BaseTooltip>
      <BaseTooltipTrigger asChild>
        <BaseButton
          aria-label={t('Quote post')}
          size="sm"
          className="max-h-5.5 p-1! text-2xs! lg:text-xs!"
          onClick={handleClick}
        >
          <LuQuote className="size-3!" />
        </BaseButton>
      </BaseTooltipTrigger>

      <BaseTooltipContent className="text-xs">{t('Quote post')}</BaseTooltipContent>
    </BaseTooltip>
  );
};
