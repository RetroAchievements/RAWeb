import type { FC } from 'react';
import { useTranslation } from 'react-i18next';
import type { IconType } from 'react-icons/lib';
import { LuShield, LuView } from 'react-icons/lu';
import { route } from 'ziggy-js';

import { InertiaLink } from '@/common/components/InertiaLink';
import { usePageProps } from '@/common/hooks/usePageProps';
import { cn } from '@/common/utils/cn';
import type { TranslatedString } from '@/types/i18next';

import { hubIds } from '../../utils/hubIds';

export const GameContentWarnings: FC = () => {
  const { hasMatureContent, hubs } = usePageProps<App.Platform.Data.GameShowPageProps>();
  const { t } = useTranslation();

  const photosensitiveWarningHub = hubs.find((hub) => hub.id === hubIds.epilepsyWarning);

  if (!hasMatureContent && !photosensitiveWarningHub) {
    return null;
  }

  return (
    <div
      data-testid="content-warnings"
      className="rounded-lg bg-embed light:border light:border-neutral-200 light:bg-white"
    >
      <div className="flex flex-col gap-0.5 rounded-lg bg-[rgba(50,50,50,0.3)] light:bg-neutral-50">
        {hasMatureContent ? (
          <ContentWarning
            href={route('hub.show', { gameSet: hubIds.mature })}
            Icon={LuShield}
            label={t('Mature Content')}
          />
        ) : null}

        {photosensitiveWarningHub ? (
          <ContentWarning
            href={route('hub.show', { gameSet: hubIds.epilepsyWarning })}
            Icon={LuView}
            label={t('Photosensitive Epilepsy Warning')}
          />
        ) : null}
      </div>
    </div>
  );
};

interface ContentWarningProps {
  href: string;
  Icon: IconType;
  label: TranslatedString;
}

const ContentWarning: FC<ContentWarningProps> = ({ href, Icon, label }) => {
  return (
    <InertiaLink href={href}>
      <div
        className={cn(
          'flex justify-center rounded-lg bg-embed p-1',
          'light:border light:border-neutral-200 light:bg-white',
        )}
      >
        <div
          className={cn(
            'flex w-full items-center justify-center gap-1.5 rounded-lg bg-zinc-800/30 p-1.5 text-2xs text-neutral-400',
            'light:bg-neutral-50 light:text-neutral-700',
          )}
        >
          <Icon className="size-4" />
          <p>{label}</p>
        </div>
      </div>
    </InertiaLink>
  );
};
