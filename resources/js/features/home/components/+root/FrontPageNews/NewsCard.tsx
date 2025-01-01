import dayjs from 'dayjs';
import { type FC, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { LuPin } from 'react-icons/lu';

import { BaseBadge } from '@/common/components/+vendor/BaseBadge';
import {
  BaseTooltip,
  BaseTooltipContent,
  BaseTooltipPortal,
  BaseTooltipTrigger,
} from '@/common/components/+vendor/BaseTooltip';
import { DiffTimestamp } from '@/common/components/DiffTimestamp';
import { usePageProps } from '@/common/hooks/usePageProps';
import { cn } from '@/common/utils/cn';
import { formatDate } from '@/common/utils/l10n/formatDate';

interface NewsCardProps {
  news: App.Data.News;

  className?: string;
  tagLabel?: string;
}

export const NewsCard: FC<NewsCardProps> = ({ news, className }) => {
  const { ziggy } = usePageProps();

  const { t } = useTranslation();

  const isRecentPost = dayjs.utc().diff(dayjs.utc(news.createdAt), 'hour') < 24;

  return (
    <a
      href={news.link ?? '#'}
      className={cn(
        'group -mx-2 cursor-pointer gap-6 rounded-xl p-1.5 sm:flex',
        'hover:bg-neutral-950/30 hover:light:bg-neutral-100',
        'border-2 border-transparent',

        ziggy.device === 'desktop' ? 'sm:bg-embed' : '',
        ziggy.device === 'mobile' ? 'bg-embed' : '',

        news.pinnedAt ? 'border-amber-600' : '',

        className,
      )}
    >
      <div className="relative h-28 w-full sm:w-[197px]">
        {news.pinnedAt && ziggy.device === 'mobile' ? (
          <div className="absolute -right-2 -top-2 flex size-8 items-center justify-center rounded-bl rounded-tr-lg bg-amber-600">
            <LuPin className="mr-[2px] size-5 text-white" />
            <p className="sr-only">{t('Pinned')}</p>
          </div>
        ) : null}

        <NewsCardImage src={news.imageAssetPath} />

        {/* {tagLabel ? (
            <div className="absolute bottom-2 right-2">
              <div className="flex h-[22px] select-none items-center justify-center rounded-full bg-neutral-50 px-2 font-bold text-zinc-900">
                {tagLabel}
              </div>
            </div>
          ) : null} */}
      </div>

      <div className="relative w-full">
        {news.pinnedAt && ziggy.device === 'desktop' ? (
          <BaseTooltip>
            <BaseTooltipTrigger className="absolute -right-2 -top-2">
              <div className="flex size-8 items-center justify-center rounded-bl rounded-tr-lg bg-amber-600">
                <LuPin className="mr-[2px] size-5 text-white" />
                <p className="sr-only">{t('Pinned')}</p>
              </div>
            </BaseTooltipTrigger>

            <BaseTooltipPortal>
              <BaseTooltipContent>
                <p className="text-xs">
                  {t('Pinned {{pinnedAt}}', { pinnedAt: formatDate(news.pinnedAt, 'll') })}
                </p>
              </BaseTooltipContent>
            </BaseTooltipPortal>
          </BaseTooltip>
        ) : null}

        <div className="mb-1 hidden text-2xs text-neutral-400/90 sm:block">
          {isRecentPost ? (
            <BaseBadge className="mr-2 bg-stone-700 px-1 py-0 text-xs font-normal text-white light:bg-white light:text-neutral-700">
              {t('new')}
            </BaseBadge>
          ) : null}

          <DiffTimestamp at={news?.createdAt} enableTooltip={false} />

          <span className="ml-1 normal-case italic">
            {'·'} {t('by {{authorDisplayName}}', { authorDisplayName: news?.user.displayName })}
          </span>
        </div>

        <p className="mb-2 mt-2 text-balance text-base sm:mt-0 md:text-wrap">
          {stripEmojis(news.title)}
        </p>
        <p className="line-clamp-3 text-text">{stripEmojis(stripHtml(news.body))}</p>
      </div>
    </a>
  );
};

interface NewsCardImageProps {
  src: string | null;
}

const NewsCardImage: FC<NewsCardImageProps> = ({ src }) => {
  const [isImageValid, setIsImageValid] = useState(!!src);

  return (
    <div className="overflow-hidden rounded">
      {/* Only img tags can detect if the image is invalid/broken (via onError). */}
      <img
        data-testid="hidden-image"
        className="sr-only"
        aria-hidden={true}
        src={src ?? undefined}
        onError={() => setIsImageValid(false)}
      />

      {isImageValid ? (
        <div
          role="img"
          aria-label="news post photo"
          className="h-28 w-full rounded bg-cover bg-center sm:w-[197px]"
          style={{
            backgroundImage: `url(${src})`,
            // TODO reintroduce (and adjust) linear gradient when tagLabel is used
            // backgroundImage: `linear-gradient(297.68deg, rgba(0, 0, 0, 0.77) 3.95%, rgba(0, 0, 0, 0) 48.13%), url(${src})`,
          }}
        />
      ) : (
        <div
          className={cn(
            'flex h-28 w-full items-center justify-center rounded bg-gradient-to-br sm:w-[197px]',
            'from-slate-800 to-zinc-950',
            'light:from-slate-100 light:to-slate-300',
          )}
        >
          <img
            src="/assets/images/ra-icon.webp"
            className="w-20 select-none"
            aria-hidden={true}
            data-testid="fallback-image"
          />
        </div>
      )}
    </div>
  );
};

/**
 * @see https://stackoverflow.com/a/68146409
 */
function stripEmojis(text: string): string {
  return text.replace(
    /\p{RI}\p{RI}|\p{Emoji}(\p{EMod}+|\u{FE0F}\u{20E3}?|[\u{E0020}-\u{E007E}]+\u{E007F})?(\u{200D}\p{Emoji}(\p{EMod}+|\u{FE0F}\u{20E3}?|[\u{E0020}-\u{E007E}]+\u{E007F})?)+|\p{EPres}(\p{EMod}+|\u{FE0F}\u{20E3}?|[\u{E0020}-\u{E007E}]+\u{E007F})?|\p{Emoji}(\p{EMod}+|\u{FE0F}\u{20E3}?|[\u{E0020}-\u{E007E}]+\u{E007F})/gu,
    '',
  );
}

function stripHtml(text: string): string {
  return text
    .replace(/<br\s*\/?>/gi, '') // Remove all <br> tags.
    .replace(/<a\b[^>]*>(.*?)<\/a>/gi, ''); // Remove <a> tags and their content.
}
