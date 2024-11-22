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

  const sortedNamedHashes = [...namedHashes].sort((a, b) => a.name!.localeCompare(b.name!));
  const sortedUnnamedHashes = [...unnamedHashes].sort((a, b) => a.md5.localeCompare(b.md5));

  return (
    <Embed data-testid={hashesListContainerTestId} className="flex flex-col gap-4">
      {sortedNamedHashes.length ? (
        <ul className="flex flex-col gap-3" data-testid="named-hashes">
          {sortedNamedHashes.map((labeledHash) => (
            <HashesListItem key={labeledHash.md5} hash={labeledHash} />
          ))}
        </ul>
      ) : null}

      {sortedNamedHashes.length && sortedUnnamedHashes.length ? <div className="my-6" /> : null}

      {sortedUnnamedHashes.length ? (
        <ul className="flex flex-col" data-testid="unnamed-hashes">
          {sortedUnnamedHashes.map((unlabeledHash) => (
            <HashesListItem key={unlabeledHash.md5} hash={unlabeledHash} />
          ))}
        </ul>
      ) : null}
    </Embed>
  );
};
