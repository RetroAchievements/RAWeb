import type { FC } from 'react';
import { useTranslation } from 'react-i18next';

import { buildTrackingClassNames } from '@/common/utils/buildTrackingClassNames';

interface HashListingProps {
  hash: App.Platform.Data.GameHash;
}

export const HashesListItem: FC<HashListingProps> = ({ hash }) => {
  const { t } = useTranslation();

  return (
    <li>
      <p className="space-x-1 sm:space-x-2">
        {hash.name ? <span className="font-bold">{hash.name}</span> : null}

        {hash.labels.length ? (
          <>
            {hash.labels.map((hashLabel, index) => (
              <HashLabel key={`${hash.md5}-${hashLabel.label}-${index}`} hashLabel={hashLabel} />
            ))}
          </>
        ) : null}
      </p>

      <div className="flex flex-col border-l-2 border-neutral-700 pl-2 light:border-embed-highlight black:border-neutral-700">
        <p className="font-mono text-neutral-200 light:text-neutral-700">{hash.md5}</p>

        {hash.patchUrl ? (
          <a
            href={hash.patchUrl}
            className={buildTrackingClassNames('Download Patch File', { md5: hash.md5 })}
          >
            {t('Download Patch File')}
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
