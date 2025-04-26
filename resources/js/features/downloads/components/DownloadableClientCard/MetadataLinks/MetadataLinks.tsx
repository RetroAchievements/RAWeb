import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import { LuBookOpen, LuExternalLink, LuGitBranch } from 'react-icons/lu';

import { BaseSeparator } from '@/common/components/+vendor/BaseSeparator';

interface MetadataLinksProps {
  emulator: App.Platform.Data.Emulator;
}

export const MetadataLinks: FC<MetadataLinksProps> = ({ emulator }) => {
  const { t } = useTranslation();

  if (!emulator.websiteUrl && !emulator.documentationUrl && !emulator.sourceUrl) {
    return null;
  }

  return (
    <div data-testid="metadata" className="flex h-4 w-full justify-center gap-5">
      {emulator.websiteUrl ? (
        <>
          <a className="flex items-center gap-1" href={emulator.websiteUrl} target="_blank">
            <LuExternalLink />
            {t('Website')}
          </a>

          {emulator.documentationUrl || emulator.sourceUrl ? (
            <BaseSeparator orientation="vertical" />
          ) : null}
        </>
      ) : null}

      {emulator.documentationUrl ? (
        <>
          <a className="flex items-center gap-1" href={emulator.documentationUrl} target="_blank">
            <LuBookOpen />
            {t('Docs')}
          </a>
          {emulator.sourceUrl ? <BaseSeparator orientation="vertical" /> : null}
        </>
      ) : null}

      {emulator.sourceUrl ? (
        <a className="flex items-center gap-1" href={emulator.sourceUrl} target="_blank">
          <LuGitBranch />
          {t('sourceCode')}
        </a>
      ) : null}
    </div>
  );
};
