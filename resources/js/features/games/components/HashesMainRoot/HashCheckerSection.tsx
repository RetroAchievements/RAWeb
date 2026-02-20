import type { ChangeEvent, FC } from 'react';
import { memo, useCallback, useRef, useState } from 'react';

import { usePageProps } from '@/common/hooks/usePageProps';
import { useRcheevos } from '@/common/hooks/useRcheevos';

interface HashCheckerSection {
  systemID: number;
}

export const HashCheckerSection: FC<HashCheckerSection> = memo(({ systemID }) => {
  const hasher = useRcheevos();
  const fileRef = useRef<HTMLInputElement>(null);
  const [hash, setHash] = useState<string | null>(null);
  const { hashes } = usePageProps<App.Platform.Data.GameHashesPageProps>();

  const onFileSelected = useCallback(
    async (event: ChangeEvent<HTMLInputElement>) => {
      const file = event.target.files?.[0];
      if (!file || !hasher.current) return;

      const result = hasher.current.computeHash(systemID, await file.arrayBuffer());

      setHash(result);
    },
    [hasher, systemID],
  );

  return (
    <div>
      <input
        max="1"
        type="file"
        ref={fileRef}
        onChange={onFileSelected}
        aria-label="Upload game file"
        data-testid="hash-file-input"
      />
      {hash && (
        <div>
          <p>
            {'Got Hash:'} <code>{hash}</code>
          </p>
          <p>{hashes.some((h) => h.md5 === hash) ? '✅' : '❌'}</p>
        </div>
      )}
    </div>
  );
});
