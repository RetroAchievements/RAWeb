import type { FC } from 'react';

import { Embed } from '@/common/components/Embed/Embed';
import { usePageProps } from '@/common/hooks/usePageProps';

import { HashesListItem } from './HashesListItem';

export const hashesListContainerTestId = 'hashes-list';

export const HashesList: FC = () => {
  const { hashes } = usePageProps<App.Platform.Data.GameHashesPageProps>();

  if (!hashes.length) {
    return null;
  }

  const namedHashes = hashes.filter((hash) => !!hash.name?.trim());
  const unnamedHashes = hashes.filter((hash) => !hash.name?.trim());

  return (
    <Embed data-testid={hashesListContainerTestId} className="flex flex-col gap-4">
      {namedHashes.length ? (
        <ul className="flex flex-col gap-3">
          {namedHashes.map((labeledHash) => (
            <HashesListItem key={labeledHash.md5} hash={labeledHash} />
          ))}
        </ul>
      ) : null}

      {namedHashes.length && unnamedHashes.length ? <div className="my-6" /> : null}

      {unnamedHashes.length ? (
        <ul className="flex flex-col">
          {unnamedHashes.map((unlabeledHash) => (
            <HashesListItem key={unlabeledHash.md5} hash={unlabeledHash} />
          ))}
        </ul>
      ) : null}
    </Embed>
  );
};
