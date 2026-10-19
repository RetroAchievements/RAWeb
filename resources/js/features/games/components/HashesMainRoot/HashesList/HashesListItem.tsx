import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuInfo } from 'react-icons/lu';

import {
  BaseTooltip,
  BaseTooltipContent,
  BaseTooltipTrigger,
} from '@/common/components/+vendor/BaseTooltip';
import { buildTrackingClassNames } from '@/common/utils/buildTrackingClassNames';
import { cn } from '@/common/utils/cn';

interface HashListingProps {
  hash: App.Platform.Data.GameHash;
}

export const HashesListItem: FC<HashListingProps> = ({ hash }) => {
  const { t } = useTranslation();

  const escapedResourceUrl = hash.resourcePageUrl ? encodeURIComponent(hash.resourcePageUrl) : null;

  return (
    <li>
      <p className="space-x-1 sm:space-x-2">
        {hash.name ? <span className="font-bold">{hash.name}</span> : null}

        {hash.labels?.length ? (
          <>
            {hash.labels.map((hashLabel, index) => (
              <HashLabel key={`${hash.md5}-${hashLabel.label}-${index}`} hashLabel={hashLabel} />
            ))}
          </>
        ) : null}

        {hash.resourcePageUrl ? (
          <BaseTooltip>
            <BaseTooltipTrigger asChild>
              <a aria-label={t('Additional information')}
                 data-testid="hash-source_link"
                 href={`/redirect?url=${escapedResourceUrl}`}>
                <LuInfo
                  className={cn(
                    'size-4 text-neutral-500 transition hover:text-neutral-300 sm:inline cursor-pointer',
                    'light:text-neutral-700',
                  )}
                />
              </a>
            </BaseTooltipTrigger>

            <BaseTooltipContent className="max-w-72 leading-normal font-normal">
              <span className="text-xs font-normal">{hash.resourcePageUrl}</span>
            </BaseTooltipContent>
          </BaseTooltip>
        ) : null}
      </p>

      <div className="flex flex-col border-l-2 border-neutral-700 pl-2 light:border-embed-highlight black:border-neutral-700">
        <p className="font-mono text-neutral-200 light:text-neutral-700">{hash.md5}</p>

        {hash.patchUrl ? (
          <a
            href={hash.patchUrl}
            className={buildTrackingClassNames('Download Compatibility Patch', { md5: hash.md5 })}
          >
            {t('Download Compatibility Patch')}
          </a>
        ) : null}
      </div>
    </li>
  );
};

interface HashLabelProps {
  hashLabel: App.Platform.Data.GameHashLabel;
}

export const HashLabel: FC<HashLabelProps> = ({ hashLabel }) => {
  const { imgSrc, label } = hashLabel;

  if (!imgSrc) {
    // eslint-disable-next-line react/jsx-no-literals -- the brackets don't need a translation
    return <span>[{label}]</span>;
  }

  return <img className="inline-image" src={imgSrc} alt={label} />;
};
