import { useLaravelReactI18n } from 'laravel-react-i18n';
import { type FC, type ReactNode, useState } from 'react';

import { cn } from '@/utils/cn';

interface NewsCardProps {
  authorDisplayName: string;
  lead: string;
  PostedAt: ReactNode;
  title: string;

  className?: string;
  href?: string;
  imageSrc?: string;
  tagLabel?: string;
}

export const NewsCard: FC<NewsCardProps> = ({
  authorDisplayName,
  href,
  imageSrc,
  lead,
  PostedAt,
  title,
  className,
}) => {
  const { t } = useLaravelReactI18n();

  return (
    <a
      href={href}
      className={cn(
        'group -mx-2 cursor-pointer gap-6 rounded-xl bg-embed p-2 sm:flex',
        'hover:bg-neutral-950/30 hover:light:bg-neutral-100',
        className,
      )}
    >
      <div className="relative h-28 w-full sm:w-[197px]">
        <NewsCardImage src={imageSrc} />

        {/* {tagLabel ? (
            <div className="absolute bottom-2 right-2">
              <div className="flex h-[22px] select-none items-center justify-center rounded-full bg-neutral-50 px-2 font-bold text-zinc-900">
                {tagLabel}
              </div>
            </div>
          ) : null} */}
      </div>

      <div>
        <p className="mb-1 hidden text-2xs text-neutral-400/90 sm:block">
          {PostedAt}{' '}
          <span className="normal-case italic">
            {'·'} {t('by :authorDisplayName', { authorDisplayName })}
          </span>
        </p>

        <p className="mb-2 mt-2 text-balance text-base sm:mt-0 md:text-wrap">
          {stripEmojis(title)}
        </p>
        <p className="line-clamp-3 text-text">{stripEmojis(stripHtml(lead))}</p>
      </div>
    </a>
  );
};

interface NewsCardImageProps {
  src?: string;
}

const NewsCardImage: FC<NewsCardImageProps> = ({ src }) => {
  const [isImageValid, setIsImageValid] = useState(!!src);

  return (
    <div className="overflow-hidden rounded">
      {/* Only img tags can detect if the image is invalid/broken (via onError). */}
      <img
        className="sr-only"
        aria-hidden={true}
        src={src}
        onError={() => setIsImageValid(false)}
      />

      {isImageValid ? (
        <div
          className="h-28 w-full rounded object-cover sm:w-[197px]"
          style={{
            backgroundSize: 'cover',
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
